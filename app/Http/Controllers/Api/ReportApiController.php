<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Services\ComparativeReportService;
use Illuminate\Http\Request;

class ReportApiController extends Controller
{
    public function __construct(private ComparativeReportService $comparativeReportService)
    {
    }

    /**
     * GET /api/reports/groups-comparison
     * Optional query params: year, date_from, date_to.
     */
    public function groupsComparison(Request $request)
    {
        $user = $request->user();
        if (!$user->isAdmin() && !$user->isAdminGrupo()) {
            abort(403);
        }

        $filters = $request->validate([
            'year'      => 'nullable|integer|min:2000|max:2100',
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date',
        ]);

        $groupIds = $this->resolveGroupIds($user);

        $result = $this->comparativeReportService->comparativeGroups($filters, $groupIds);

        $data = collect($result['rows'])->map(fn($row) => [
            'group_id'          => $row['group']->id,
            'group_name'        => $row['group']->name,
            'registration_mode' => $row['group']->registration_mode ?? 'full',
            'active_members'    => $row['active_members'],
            'total_savings'     => (float) $row['total_savings'],
            'total_emergency'   => (float) $row['total_emergency'],
            'total_fines'       => (float) $row['total_fines'],
            'loans_out'         => (float) $row['total_loans_out'],
            'loans_recovered'   => (float) $row['total_loans_recov'],
            'loans_balance'     => (float) $row['total_loans_bal'],
            'total_interest'    => (float) $row['total_interest'],
            'attendance_rate'   => (float) $row['attendance_rate'],
        ])->values();

        return response()->json(['data' => $data]);
    }

    private const SUMMARY_SECTIONS = ['monthly', 'sessions', 'top_savers', 'top_attendance'];

    /**
     * GET /api/reports/groups/{group}/summary
     * Optional query params: year, sections (comma-separated subset of
     * monthly,sessions,top_savers,top_attendance — absent/empty means all;
     * unknown tokens are ignored). Only requested sections are computed;
     * "group" is always included.
     */
    public function groupSummary(Request $request, Group $group)
    {
        $user = $request->user();
        if (!$user->isAdmin() && !$user->isAdminGrupo()) {
            abort(403);
        }

        $groupIds = $this->resolveGroupIds($user);
        if (!$groupIds->contains($group->id)) {
            abort(403);
        }

        $filters = $request->validate([
            'year'     => 'nullable|integer|min:2000|max:2100',
            'sections' => 'nullable|string',
        ]);

        $sections = $this->resolveSections($filters['sections'] ?? null);
        unset($filters['sections']);

        $data = [
            'group' => [
                'id'                => $group->id,
                'name'              => $group->name,
                'registration_mode' => $group->registration_mode ?? 'full',
            ],
        ];

        $wantsMonthly  = in_array('monthly', $sections);
        $wantsSessions = in_array('sessions', $sections);

        if ($wantsMonthly || $wantsSessions) {
            $periodsResult = $this->comparativeReportService->comparativePeriods(
                array_merge($filters, ['group_id' => $group->id]),
                $groupIds,
                withSessions: $wantsSessions
            );

            if ($wantsMonthly) {
                $data['monthly'] = collect($periodsResult['periods'])->map(fn($period) => [
                    'period'          => $period['period'],
                    'label'           => $period['label'],
                    'savings'         => (float) $period['savings'],
                    'fines'           => (float) $period['fines'],
                    'loans_out'       => (float) $period['loans_out'],
                    'loan_payments'   => (float) $period['loan_payments'],
                    'attendance_rate' => (float) $period['attendance_rate'],
                    'savings_delta'   => $period['savings_delta'] !== null ? (float) $period['savings_delta'] : null,
                ])->values();
            }

            if ($wantsSessions) {
                $data['sessions'] = $periodsResult['sessions'];
            }
        }

        $wantsSavers     = in_array('top_savers', $sections);
        $wantsAttendance = in_array('top_attendance', $sections);

        if ($wantsSavers || $wantsAttendance) {
            $rankings = $this->comparativeReportService->memberRankings(
                $group,
                $filters,
                withSavers: $wantsSavers,
                withAttendance: $wantsAttendance
            );

            if ($wantsSavers) {
                $data['top_savers'] = collect($rankings['top_savers'])->map(fn($m) => [
                    'member_id'     => $m['member_id'],
                    'name'          => $m['name'],
                    'total_saved'   => (float) $m['total_saved'],
                    'contributions' => $m['contributions'],
                ])->values();
            }

            if ($wantsAttendance) {
                $data['top_attendance'] = collect($rankings['top_attendance'])->map(fn($m) => [
                    'member_id'       => $m['member_id'],
                    'name'            => $m['name'],
                    'attended'        => $m['attended'],
                    'attendance_rate' => (float) $m['attendance_rate'],
                ])->values();
            }
        }

        return response()->json(['data' => $data]);
    }

    /**
     * Whitelist-filter the sections query param. Absent or empty means all
     * sections; unknown tokens are dropped (all-unknown yields none).
     */
    private function resolveSections(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return self::SUMMARY_SECTIONS;
        }

        $tokens = array_map('trim', explode(',', $raw));

        return array_values(array_intersect(self::SUMMARY_SECTIONS, $tokens));
    }

    private function resolveGroupIds($user)
    {
        return $user->isAdmin() ? Group::pluck('id') : $user->groups()->pluck('groups.id');
    }
}
