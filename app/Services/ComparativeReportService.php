<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Meeting;
use App\Models\Member;

class ComparativeReportService
{
    /**
     * Compare all groups in scope for a given period.
     * Mirrors ReportController::comparativeGroupsReport() — same array shape.
     */
    public function comparativeGroups(array $filters, $groupIds): array
    {
        $groupQuery = Group::whereIn('id', $groupIds)
            ->with(['members', 'meetings.contributions', 'meetings.totals', 'meetings.fines', 'meetings.loans', 'meetings.loanPayments', 'meetings.attendances']);
        if (!empty($filters['group_id'])) $groupQuery->where('id', $filters['group_id']);
        $groups = $groupQuery->get();

        $rows = [];
        foreach ($groups as $group) {
            $meetings = $group->meetings->filter(function ($meeting) use ($filters) {
                if (!empty($filters['date_from']) && $meeting->meeting_date->lt($filters['date_from'])) return false;
                if (!empty($filters['date_to'])   && $meeting->meeting_date->gt($filters['date_to']))   return false;
                if (!empty($filters['year'])      && $meeting->meeting_date->year != $filters['year'])  return false;
                return true;
            });

            // Partial-registration meetings store amounts in a totals row instead
            // of per-member contribution rows, so both sources are added.
            $meetingTotals   = $meetings->pluck('totals')->filter();
            $totalSavings    = $meetings->flatMap->contributions->sum('savings') + $meetingTotals->sum('savings');
            $totalEmergency  = $meetings->flatMap->contributions->sum('emergency_fund') + $meetingTotals->sum('emergency_fund');
            $totalFines      = $meetings->flatMap->fines->where('status', 'paid')->sum('amount') + $meetingTotals->sum('fine');
            $totalLoansOut   = $meetings->flatMap->loans->sum('amount');
            $totalLoansRecov = $meetings->flatMap->loans->sum('amount_paid');
            $totalLoansBal   = $meetings->flatMap->loans->sum('balance');
            $totalInterest   = $meetings->flatMap->loanPayments->sum('interest_paid');

            $totalAttendances = $meetings->flatMap->attendances;
            $attendedCount    = $totalAttendances->whereIn('status', ['present', 'late'])->count();
            $attendanceRate   = $totalAttendances->count() > 0 ? round(($attendedCount / $totalAttendances->count()) * 100, 1) : 0;

            $rows[] = [
                'group'             => $group,
                'active_members'    => $group->members->where('status', 'active')->count(),
                'total_savings'     => $totalSavings,
                'total_emergency'   => $totalEmergency,
                'total_fines'       => $totalFines,
                'total_loans_out'   => $totalLoansOut,
                'total_loans_recov' => $totalLoansRecov,
                'total_loans_bal'   => $totalLoansBal,
                'total_interest'    => $totalInterest,
                'attendance_rate'   => $attendanceRate,
            ];
        }

        return ['rows' => $rows, 'filters' => $filters];
    }

    /**
     * Compare monthly periods within a single group.
     * Mirrors ReportController::comparativePeriodsReport() — same array shape.
     * Caller is responsible for the group-ownership abort(403) check.
     *
     * When $withSessions is true the result also includes a 'sessions' array
     * with one per-meeting breakdown row (API-only; web callers keep the
     * default so their view data shape is unchanged).
     */
    public function comparativePeriods(array $filters, $groupIds, bool $withSessions = false): array
    {
        if (empty($filters['group_id']) || !$groupIds->contains($filters['group_id'])) {
            abort(403);
        }

        $meetingQuery = Meeting::where('group_id', $filters['group_id'])
            ->with(['contributions', 'totals', 'fines', 'loans', 'loanPayments', 'attendances'])
            ->orderBy('meeting_date');
        if (!empty($filters['year'])) $meetingQuery->whereYear('meeting_date', $filters['year']);
        $meetings = $meetingQuery->get();

        $periods = [];
        foreach ($meetings as $meeting) {
            $key = $meeting->meeting_date->format('Y-m');
            if (!isset($periods[$key])) {
                $periods[$key] = [
                    'period'          => $key,
                    'label'           => $meeting->meeting_date->translatedFormat('M Y'),
                    'savings'         => 0,
                    'fines'           => 0,
                    'loans_out'       => 0,
                    'loan_payments'   => 0,
                    'attended'        => 0,
                    'total_attend'    => 0,
                ];
            }
            $periods[$key]['savings']       += $meeting->contributions->sum('savings') + ($meeting->totals?->savings ?? 0);
            $periods[$key]['fines']         += $meeting->fines->where('status', 'paid')->sum('amount') + ($meeting->totals?->fine ?? 0);
            $periods[$key]['loans_out']     += $meeting->loans->sum('amount');
            $periods[$key]['loan_payments'] += $meeting->loanPayments->sum('amount_paid');
            $periods[$key]['attended']      += $meeting->attendances->whereIn('status', ['present', 'late'])->count();
            $periods[$key]['total_attend']  += $meeting->attendances->count();
        }

        $previousSavings = null;
        foreach ($periods as &$row) {
            $row['attendance_rate'] = $row['total_attend'] > 0 ? round(($row['attended'] / $row['total_attend']) * 100, 1) : 0;
            $row['savings_delta']   = $previousSavings !== null && $previousSavings > 0
                ? round((($row['savings'] - $previousSavings) / $previousSavings) * 100, 1)
                : null;
            $previousSavings = $row['savings'];
        }

        $result = ['periods' => array_values($periods), 'filters' => $filters];

        if ($withSessions) {
            $result['sessions'] = $this->meetingSessions($meetings);
        }

        return $result;
    }

    /**
     * Per-meeting breakdown rows built from an already-loaded meetings
     * collection (contributions, totals, fines, attendances must be eager
     * loaded by the caller). Money sums add both sources — per-member
     * contributions plus the partial-registration MeetingTotal row — matching
     * the monthly computation so the same meeting shows the same amounts.
     */
    private function meetingSessions($meetings): array
    {
        return $meetings->sortBy('meeting_number')->map(function ($meeting) {
            $attended     = $meeting->attendances->whereIn('status', ['present', 'late'])->count();
            $totalMembers = $meeting->attendances->count();

            return [
                'meeting_id'      => $meeting->id,
                'number'          => $meeting->meeting_number,
                'date'            => $meeting->meeting_date->format('Y-m-d'),
                'status'          => $meeting->status,
                'attended'        => $attended,
                'total_members'   => $totalMembers,
                'attendance_rate' => $totalMembers > 0 ? round(($attended / $totalMembers) * 100, 1) : 0,
                'savings'         => (float) ($meeting->contributions->sum('savings') + ($meeting->totals?->savings ?? 0)),
                'emergency'       => (float) ($meeting->contributions->sum('emergency_fund') + ($meeting->totals?->emergency_fund ?? 0)),
                'fines'           => (float) ($meeting->fines->where('status', 'paid')->sum('amount') + ($meeting->totals?->fine ?? 0)),
            ];
        })->values()->all();
    }

    /**
     * Member rankings within a single group: top savers and top attendance.
     * Mirrors ReportController::memberRankingReport() ranking logic, scoped
     * to one group and split into two independent top-10 lists.
     *
     * $withSavers / $withAttendance let callers skip a list entirely: the
     * related rows are not eager loaded and the list is returned empty.
     */
    public function memberRankings(Group $group, array $filters = [], bool $withSavers = true, bool $withAttendance = true): array
    {
        // Partial-registration groups only record aggregate MeetingTotal rows,
        // so per-member savings are always zero — a savers ranking is meaningless.
        $computeSavers = $withSavers && !$group->isPartial();

        $relations = [];
        if ($computeSavers)   $relations[] = 'contributions';
        if ($withAttendance)  $relations[] = 'attendances';

        $members = Member::where('group_id', $group->id)
            ->with($relations)
            ->where('status', 'active')
            ->get();

        $totalMeetings = $withAttendance
            ? Meeting::where('group_id', $group->id)
                ->when(!empty($filters['year']), fn($q) => $q->whereYear('meeting_date', $filters['year']))
                ->count()
            : 0;

        $topSavers = !$computeSavers
            ? collect()
            : $members->map(function ($member) {
                return [
                    'member_id'     => $member->id,
                    'name'          => $member->full_name,
                    'total_saved'   => (float) $member->contributions->sum('savings'),
                    'contributions' => $member->contributions->where('shares', '>', 0)->count(),
                ];
            })->sortByDesc('total_saved')->take(10)->values();

        $topAttendance = !$withAttendance
            ? collect()
            : $members->map(function ($member) use ($totalMeetings) {
                $attended = $member->attendances->whereIn('status', ['present', 'late'])->count();
                return [
                    'member_id'       => $member->id,
                    'name'            => $member->full_name,
                    'attended'        => $attended,
                    'attendance_rate' => $totalMeetings > 0 ? round(($attended / $totalMeetings) * 100, 1) : 0.0,
                ];
            })->sortByDesc('attendance_rate')->take(10)->values();

        return [
            'top_savers'     => $topSavers,
            'top_attendance' => $topAttendance,
            'total_meetings' => $totalMeetings,
        ];
    }
}
