<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Member;
use App\Models\Meeting;
use App\Models\Loan;
use App\Models\Fine;
use App\Models\MeetingContribution;
use App\Models\Attendance;
use App\Models\BankExpense;
use App\Exports\GroupReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    private const REPORT_TYPES = [
        'group', 'member', 'meeting', 'loans_pending', 'loans_paid', 'monthly',
        'financial_summary', 'savings_evolution', 'cash_statement',
        'member_contributions', 'member_ranking', 'delinquent_members',
        'loan_history', 'active_loans', 'loan_recovery', 'loan_profitability',
        'meeting_attendance', 'member_participation',
        'fines_generated', 'fines_status',
        'comparative_groups', 'comparative_periods',
    ];

    private const COMPARATIVE_REPORT_TYPES = ['comparative_groups', 'comparative_periods'];

    public function index()
    {
        $user   = auth()->user();
        $groups = $user->isAdmin() ? Group::all() : $user->groups()->get();
        return view('reports.index', compact('groups'));
    }

    public function generate(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'report_type' => 'required|in:' . implode(',', self::REPORT_TYPES),
            'group_id'    => 'nullable|exists:groups,id',
            'member_id'   => 'nullable|exists:members,id',
            'month'       => 'nullable|string',
            'year'        => 'nullable|integer|min:2000|max:2100',
            'date_from'   => 'nullable|date',
            'date_to'     => 'nullable|date',
            'format'      => 'required|in:pdf,excel',
        ]);

        if (in_array($data['report_type'], self::COMPARATIVE_REPORT_TYPES) && !$user->isAdmin() && !$user->isAdminGrupo()) {
            abort(403);
        }

        if ($data['report_type'] === 'comparative_periods' && empty($data['group_id'])) {
            return back()->withErrors(['group_id' => 'El grupo es obligatorio para el reporte comparativo de períodos.']);
        }

        $reportData = $this->getReportData($data);

        if ($data['format'] === 'pdf') {
            $pdf = Pdf::loadView('reports.pdf.' . $data['report_type'], $reportData)
                ->setPaper('a4', 'landscape');
            return $pdf->download('reporte_' . $data['report_type'] . '_' . now()->format('Ymd') . '.pdf');
        }

        return Excel::download(
            new GroupReportExport($reportData, $data['report_type']),
            'reporte_' . $data['report_type'] . '_' . now()->format('Ymd') . '.xlsx'
        );
    }

    public function membersByGroup(Group $group)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->groups()->where('groups.id', $group->id)->exists()) {
            abort(403);
        }
        return response()->json(
            $group->members()->where('status', 'active')->orderBy('full_name')->get(['id', 'full_name'])
        );
    }

    private function getReportData(array $filters): array
    {
        $user     = auth()->user();
        $groupIds = $user->isAdmin()
            ? Group::pluck('id')
            : $user->groups()->pluck('groups.id');

        return match ($filters['report_type']) {
            'group'                => $this->groupReport($filters, $groupIds),
            'member'               => $this->memberReport($filters, $groupIds),
            'meeting'              => $this->meetingReport($filters, $groupIds),
            'loans_pending'        => $this->loanReport($filters, $groupIds, 'pending'),
            'loans_paid'           => $this->loanReport($filters, $groupIds, 'paid'),
            'monthly'              => $this->monthlyReport($filters, $groupIds),
            'financial_summary'    => $this->financialSummaryReport($filters, $groupIds),
            'savings_evolution'    => $this->savingsEvolutionReport($filters, $groupIds),
            'cash_statement'       => $this->cashStatementReport($filters, $groupIds),
            'member_contributions' => $this->memberContributionsReport($filters, $groupIds),
            'member_ranking'       => $this->memberRankingReport($filters, $groupIds),
            'delinquent_members'   => $this->delinquentMembersReport($filters, $groupIds),
            'loan_history'         => $this->loanHistoryReport($filters, $groupIds),
            'active_loans'         => $this->activeLoansReport($filters, $groupIds),
            'loan_recovery'        => $this->loanRecoveryReport($filters, $groupIds),
            'loan_profitability'   => $this->loanProfitabilityReport($filters, $groupIds),
            'meeting_attendance'   => $this->meetingAttendanceReport($filters, $groupIds),
            'member_participation' => $this->memberParticipationReport($filters, $groupIds),
            'fines_generated'      => $this->finesGeneratedReport($filters, $groupIds),
            'fines_status'         => $this->finesStatusReport($filters, $groupIds),
            'comparative_groups'   => $this->comparativeGroupsReport($filters, $groupIds),
            'comparative_periods'  => $this->comparativePeriodsReport($filters, $groupIds),
            default                => [],
        };
    }

    // ──────────────────────────────────────────────────────────────
    // Original reports
    // ──────────────────────────────────────────────────────────────

    private function groupReport(array $filters, $groupIds): array
    {
        $query = Group::whereIn('id', $groupIds)->with(['members', 'meetings']);
        if (!empty($filters['group_id'])) $query->where('id', $filters['group_id']);
        return ['groups' => $query->get(), 'filters' => $filters];
    }

    private function meetingReport(array $filters, $groupIds): array
    {
        $query = Meeting::whereIn('group_id', $groupIds)
            ->with(['group', 'contributions'])
            ->orderBy('meeting_date', 'desc');
        if (!empty($filters['group_id'])) $query->where('group_id', $filters['group_id']);
        if (!empty($filters['month']))    $query->where('month', $filters['month']);
        if (!empty($filters['date_from'])) $query->where('meeting_date', '>=', $filters['date_from']);
        if (!empty($filters['date_to']))   $query->where('meeting_date', '<=', $filters['date_to']);
        return ['meetings' => $query->get(), 'filters' => $filters];
    }

    private function loanReport(array $filters, $groupIds, string $status): array
    {
        $query = Loan::whereIn('group_id', $groupIds)->where('status', $status)
            ->with(['member', 'group', 'meeting', 'payments']);
        if (!empty($filters['group_id'])) $query->where('group_id', $filters['group_id']);
        return ['loans' => $query->get(), 'filters' => $filters, 'status' => $status];
    }

    private function memberReport(array $filters, $groupIds): array
    {
        $query = Member::whereIn('group_id', $groupIds)->with(['group', 'contributions', 'loans', 'fines']);
        if (!empty($filters['group_id']))  $query->whereIn('group_id', [$filters['group_id']]);
        if (!empty($filters['member_id'])) $query->where('id', $filters['member_id']);
        return ['members' => $query->get(), 'filters' => $filters];
    }

    private function monthlyReport(array $filters, $groupIds): array
    {
        $query = MeetingContribution::whereHas('meeting', fn($q) => $q->whereIn('group_id', $groupIds))
            ->with(['meeting.group', 'member']);
        if (!empty($filters['month'])) {
            $query->whereHas('meeting', fn($q) => $q->where('month', $filters['month']));
        }
        if (!empty($filters['group_id'])) {
            $query->whereHas('meeting', fn($q) => $q->where('group_id', $filters['group_id']));
        }
        return ['contributions' => $query->get(), 'filters' => $filters];
    }

    // ──────────────────────────────────────────────────────────────
    // Financial reports (1–3)
    // ──────────────────────────────────────────────────────────────

    private function financialSummaryReport(array $filters, $groupIds): array
    {
        $groupQuery = Group::whereIn('id', $groupIds)
            ->with(['members', 'meetings.contributions', 'meetings.totals', 'meetings.fines', 'meetings.loans', 'meetings.loanPayments', 'meetings.bankExpenses']);
        if (!empty($filters['group_id'])) $groupQuery->where('id', $filters['group_id']);
        $groups = $groupQuery->get();

        $summary = [];
        foreach ($groups as $group) {
            // Partial-registration meetings store amounts in a totals row instead
            // of per-member contribution rows, so both sources are added.
            $meetingTotals     = $group->meetings->pluck('totals')->filter();
            $totalSavings      = $group->meetings->flatMap->contributions->sum('savings') + $meetingTotals->sum('savings');
            $totalEmergency    = $group->meetings->flatMap->contributions->sum('emergency_fund') + $meetingTotals->sum('emergency_fund');
            $totalFines        = $group->meetings->flatMap->fines->where('status', 'paid')->sum('amount') + $meetingTotals->sum('fine');
            $totalLoansOut     = $group->meetings->flatMap->loans->sum('amount');
            $totalLoansRecov   = $group->meetings->flatMap->loans->sum('amount_paid');
            $totalBankExpenses = $group->meetings->flatMap->bankExpenses->sum('amount');
            $totalInterest     = $group->meetings->flatMap->loanPayments->sum('interest_paid');
            $lastSummary       = $group->meetings->sortByDesc('meeting_number')->first()?->summary;
            $availableBalance  = $lastSummary?->total_group_funds ?? 0;

            $summary[] = [
                'group'              => $group,
                'total_savings'      => $totalSavings,
                'total_emergency'    => $totalEmergency,
                'total_fines'        => $totalFines,
                'total_loans_out'    => $totalLoansOut,
                'total_loans_recov'  => $totalLoansRecov,
                'total_bank_expenses'=> $totalBankExpenses,
                'total_interest'     => $totalInterest,
                'available_balance'  => $availableBalance,
            ];
        }

        return ['summary' => $summary, 'filters' => $filters];
    }

    private function savingsEvolutionReport(array $filters, $groupIds): array
    {
        $meetingQuery = Meeting::whereIn('group_id', $groupIds)
            ->with(['contributions', 'totals', 'group', 'summary'])
            ->orderBy('meeting_date');
        if (!empty($filters['group_id'])) $meetingQuery->where('group_id', $filters['group_id']);
        if (!empty($filters['year']))     $meetingQuery->whereYear('meeting_date', $filters['year']);
        $meetings = $meetingQuery->get();

        $monthlyData = [];
        foreach ($meetings as $meeting) {
            $key = $meeting->meeting_date->format('Y-m');
            if (!isset($monthlyData[$key])) {
                $monthlyData[$key] = [
                    'label'       => $meeting->meeting_date->translatedFormat('M Y'),
                    'savings'     => 0,
                    'accumulated' => 0,
                ];
            }
            $monthlyData[$key]['savings'] += $meeting->contributions->sum('savings') + ($meeting->totals?->savings ?? 0);
        }

        $accumulated = 0;
        foreach ($monthlyData as &$row) {
            $accumulated         += $row['savings'];
            $row['accumulated']   = $accumulated;
        }

        return ['monthly_data' => array_values($monthlyData), 'meetings' => $meetings, 'filters' => $filters];
    }

    private function cashStatementReport(array $filters, $groupIds): array
    {
        $meetingQuery = Meeting::whereIn('group_id', $groupIds)
            ->with(['contributions', 'totals', 'loans', 'loanPayments', 'fines', 'bankExpenses', 'summary', 'group'])
            ->orderBy('meeting_date');
        if (!empty($filters['group_id'])) $meetingQuery->where('group_id', $filters['group_id']);
        if (!empty($filters['year']))     $meetingQuery->whereYear('meeting_date', $filters['year']);
        $meetings = $meetingQuery->get();

        $rows = [];
        foreach ($meetings as $meeting) {
            $incomeSavings  = $meeting->contributions->sum('savings') + ($meeting->totals?->savings ?? 0);
            $incomeInterest = $meeting->loanPayments->sum('interest_paid');
            $incomeFines    = $meeting->fines->where('status', 'paid')->sum('amount') + ($meeting->totals?->fine ?? 0);
            $totalIncome    = $incomeSavings + $incomeInterest + $incomeFines;
            $loansOut       = $meeting->loans->sum('amount');
            $bankExpenses   = $meeting->bankExpenses->sum('amount');
            $totalExpenses  = $loansOut + $bankExpenses;
            $previous       = $meeting->summary?->previous_total ?? 0;
            $balance        = $meeting->summary?->total_group_funds ?? ($previous + $totalIncome - $totalExpenses);

            $rows[] = [
                'meeting'        => $meeting,
                'previous'       => $previous,
                'income_savings' => $incomeSavings,
                'income_interest'=> $incomeInterest,
                'income_fines'   => $incomeFines,
                'total_income'   => $totalIncome,
                'loans_out'      => $loansOut,
                'bank_expenses'  => $bankExpenses,
                'total_expenses' => $totalExpenses,
                'balance'        => $balance,
            ];
        }

        return ['rows' => $rows, 'filters' => $filters];
    }

    // ──────────────────────────────────────────────────────────────
    // Member reports (4–6)
    // ──────────────────────────────────────────────────────────────

    private function memberContributionsReport(array $filters, $groupIds): array
    {
        $query = Member::whereIn('group_id', $groupIds)
            ->with(['group', 'contributions', 'attendances'])
            ->where('status', 'active');
        if (!empty($filters['group_id']))  $query->where('group_id', $filters['group_id']);
        if (!empty($filters['member_id'])) $query->where('id', $filters['member_id']);
        $members = $query->get();

        $totalMeetings = Meeting::whereIn('group_id', $groupIds)
            ->when(!empty($filters['group_id']), fn($q) => $q->where('group_id', $filters['group_id']))
            ->count();

        $rows = $members->map(function ($member) use ($totalMeetings) {
            $paid    = $member->contributions->where('shares', '>', 0)->count();
            $pending = max(0, $totalMeetings - $paid);
            $pct     = $totalMeetings > 0 ? round(($paid / $totalMeetings) * 100, 1) : 0;
            return [
                'member'       => $member,
                'total_saved'  => $member->total_savings,
                'paid_count'   => $paid,
                'pending_count'=> $pending,
                'compliance'   => $pct,
            ];
        })->sortByDesc('total_saved')->values();

        return ['rows' => $rows, 'total_meetings' => $totalMeetings, 'filters' => $filters];
    }

    private function memberRankingReport(array $filters, $groupIds): array
    {
        $query = Member::whereIn('group_id', $groupIds)
            ->with(['group', 'contributions', 'attendances'])
            ->where('status', 'active');
        if (!empty($filters['group_id'])) $query->where('group_id', $filters['group_id']);
        $members = $query->get();

        $totalMeetings = Meeting::whereIn('group_id', $groupIds)
            ->when(!empty($filters['group_id']), fn($q) => $q->where('group_id', $filters['group_id']))
            ->count();

        $ranked = $members->map(function ($member) use ($totalMeetings) {
            $attended   = $member->attendances->whereIn('status', ['present', 'late'])->count();
            $paid       = $member->contributions->where('shares', '>', 0)->count();
            $punctuality = $totalMeetings > 0 ? round(($attended / max($totalMeetings, 1)) * 100, 1) : 0;
            return [
                'member'      => $member,
                'total_saved' => $member->total_savings,
                'punctuality' => $punctuality,
                'attended'    => $attended,
                'paid'        => $paid,
            ];
        });

        return [
            'top_savers'     => $ranked->sortByDesc('total_saved')->take(10)->values(),
            'top_punctual'   => $ranked->sortByDesc('punctuality')->take(10)->values(),
            'total_meetings' => $totalMeetings,
            'filters'        => $filters,
        ];
    }

    private function delinquentMembersReport(array $filters, $groupIds): array
    {
        $query = Member::whereIn('group_id', $groupIds)
            ->with(['group', 'fines' => fn($q) => $q->where('status', 'pending')->with('meeting'), 'loans'])
            ->where('status', 'active');
        if (!empty($filters['group_id'])) $query->where('group_id', $filters['group_id']);
        $members = $query->get();

        $rows = $members->filter(function ($member) {
            return $member->fines->where('status', 'pending')->count() > 0
                || $member->loans->whereIn('status', ['pending', 'overdue'])->count() > 0;
        })->map(function ($member) {
            $pendingFines    = $member->fines->where('status', 'pending');
            $overdueFines    = $pendingFines->filter(fn($f) => $f->meeting?->meeting_date?->diffInDays(now()) > 30);
            $activeLoans     = $member->loans->whereIn('status', ['pending', 'overdue']);
            return [
                'member'        => $member,
                'pending_fines' => $pendingFines,
                'overdue_days'  => $overdueFines->max(fn($f) => $f->meeting?->meeting_date?->diffInDays(now())) ?? 0,
                'fines_amount'  => $pendingFines->sum('amount'),
                'active_loans'  => $activeLoans,
                'loans_balance' => $activeLoans->sum('balance'),
            ];
        })->sortByDesc('fines_amount')->values();

        return ['rows' => $rows, 'filters' => $filters];
    }

    // ──────────────────────────────────────────────────────────────
    // Loan reports (7–10)
    // ──────────────────────────────────────────────────────────────

    private function loanHistoryReport(array $filters, $groupIds): array
    {
        $query = Loan::whereIn('group_id', $groupIds)
            ->with(['member', 'group', 'meeting', 'payments'])
            ->orderBy('delivery_date', 'desc');
        if (!empty($filters['group_id']))  $query->where('group_id', $filters['group_id']);
        if (!empty($filters['member_id'])) $query->where('member_id', $filters['member_id']);
        if (!empty($filters['year']))      $query->whereYear('delivery_date', $filters['year']);
        return ['loans' => $query->get(), 'filters' => $filters];
    }

    private function activeLoansReport(array $filters, $groupIds): array
    {
        $query = Loan::whereIn('group_id', $groupIds)
            ->whereIn('status', ['pending', 'overdue'])
            ->with(['member', 'group', 'meeting', 'payments'])
            ->orderBy('due_date');
        if (!empty($filters['group_id'])) $query->where('group_id', $filters['group_id']);
        $loans = $query->get();

        $rows = $loans->map(function ($loan) {
            $installmentsPaid = $loan->payments->count();
            return [
                'loan'               => $loan,
                'installments_paid'  => $installmentsPaid,
                'days_to_due'        => $loan->due_date ? now()->diffInDays($loan->due_date, false) : null,
            ];
        });

        return ['rows' => $rows, 'filters' => $filters];
    }

    private function loanRecoveryReport(array $filters, $groupIds): array
    {
        $groupQuery = Group::whereIn('id', $groupIds)
            ->with(['meetings.loans.payments']);
        if (!empty($filters['group_id'])) $groupQuery->where('id', $filters['group_id']);
        $groups = $groupQuery->get();

        $totals = [];
        foreach ($groups as $group) {
            $loans = $group->meetings->flatMap->loans;
            $totals[] = [
                'group'     => $group,
                'loaned'    => $loans->sum('amount'),
                'recovered' => $loans->sum('amount_paid'),
                'pending'   => $loans->sum('balance'),
            ];
        }

        return ['totals' => $totals, 'filters' => $filters];
    }

    private function loanProfitabilityReport(array $filters, $groupIds): array
    {
        $query = Loan::whereIn('group_id', $groupIds)
            ->with(['member', 'group', 'payments'])
            ->orderBy('delivery_date', 'desc');
        if (!empty($filters['group_id'])) $query->where('group_id', $filters['group_id']);
        if (!empty($filters['year']))     $query->whereYear('delivery_date', $filters['year']);
        $loans = $query->get();

        $totalInterestCharged  = $loans->sum('interest_amount');
        $totalInterestCollected = $loans->flatMap->payments->sum('interest_paid');

        return [
            'loans'                    => $loans,
            'total_interest_charged'   => $totalInterestCharged,
            'total_interest_collected' => $totalInterestCollected,
            'filters'                  => $filters,
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // Meeting reports (11–12)
    // ──────────────────────────────────────────────────────────────

    private function meetingAttendanceReport(array $filters, $groupIds): array
    {
        $meetingQuery = Meeting::whereIn('group_id', $groupIds)
            ->with(['group', 'attendances.member'])
            ->orderBy('meeting_date');
        if (!empty($filters['group_id'])) $meetingQuery->where('group_id', $filters['group_id']);
        if (!empty($filters['year']))     $meetingQuery->whereYear('meeting_date', $filters['year']);
        $meetings = $meetingQuery->get();

        $memberQuery = Member::whereIn('group_id', $groupIds)
            ->with(['attendances'])
            ->where('status', 'active');
        if (!empty($filters['group_id'])) $memberQuery->where('group_id', $filters['group_id']);
        $members = $memberQuery->get();

        $totalMeetings = $meetings->count();

        $memberRows = $members->map(function ($member) use ($totalMeetings) {
            $attended = $member->attendances->whereIn('status', ['present', 'late'])->count();
            $pct      = $totalMeetings > 0 ? round(($attended / $totalMeetings) * 100, 1) : 0;
            return [
                'member'   => $member,
                'attended' => $attended,
                'absent'   => $totalMeetings - $attended,
                'pct'      => $pct,
            ];
        })->sortByDesc('pct')->values();

        return [
            'meetings'       => $meetings,
            'member_rows'    => $memberRows,
            'total_meetings' => $totalMeetings,
            'filters'        => $filters,
        ];
    }

    private function memberParticipationReport(array $filters, $groupIds): array
    {
        $query = Member::whereIn('group_id', $groupIds)
            ->with(['group', 'attendances', 'contributions', 'loans'])
            ->where('status', 'active');
        if (!empty($filters['group_id']))  $query->where('group_id', $filters['group_id']);
        if (!empty($filters['member_id'])) $query->where('id', $filters['member_id']);
        $members = $query->get();

        $totalMeetings = Meeting::whereIn('group_id', $groupIds)
            ->when(!empty($filters['group_id']), fn($q) => $q->where('group_id', $filters['group_id']))
            ->count();

        $rows = $members->map(function ($member) use ($totalMeetings) {
            $attended = $member->attendances->whereIn('status', ['present', 'late'])->count();
            return [
                'member'           => $member,
                'attended'         => $attended,
                'attendance_pct'   => $totalMeetings > 0 ? round(($attended / $totalMeetings) * 100, 1) : 0,
                'contributions'    => $member->contributions->where('shares', '>', 0)->count(),
                'total_saved'      => $member->total_savings,
                'loans_requested'  => $member->loans->count(),
            ];
        })->sortByDesc('total_saved')->values();

        return ['rows' => $rows, 'total_meetings' => $totalMeetings, 'filters' => $filters];
    }

    // ──────────────────────────────────────────────────────────────
    // Fine reports (13–14)
    // ──────────────────────────────────────────────────────────────

    private function finesGeneratedReport(array $filters, $groupIds): array
    {
        $query = Fine::whereHas('meeting', fn($q) => $q->whereIn('group_id', $groupIds))
            ->with(['member.group', 'meeting'])
            ->orderBy('created_at', 'desc');
        if (!empty($filters['group_id'])) {
            $query->whereHas('meeting', fn($q) => $q->where('group_id', $filters['group_id']));
        }
        if (!empty($filters['member_id'])) $query->where('member_id', $filters['member_id']);
        if (!empty($filters['month'])) {
            $query->whereHas('meeting', fn($q) => $q->where('month', $filters['month']));
        }
        $fines = $query->get();

        $byMonth  = $fines->groupBy(fn($f) => $f->meeting?->month ?? 'N/A');
        $byMember = $fines->groupBy('member_id');

        return [
            'fines'     => $fines,
            'by_month'  => $byMonth,
            'by_member' => $byMember,
            'filters'   => $filters,
        ];
    }

    private function finesStatusReport(array $filters, $groupIds): array
    {
        $query = Fine::whereHas('meeting', fn($q) => $q->whereIn('group_id', $groupIds))
            ->with(['member.group', 'meeting']);
        if (!empty($filters['group_id'])) {
            $query->whereHas('meeting', fn($q) => $q->where('group_id', $filters['group_id']));
        }
        $fines = $query->get();

        $paid    = $fines->where('status', 'paid');
        $pending = $fines->where('status', 'pending');

        return [
            'fines'          => $fines,
            'paid'           => $paid,
            'pending'        => $pending,
            'total_paid'     => $paid->sum('amount'),
            'total_pending'  => $pending->sum('amount'),
            'filters'        => $filters,
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // Comparative reports (15–16)
    // ──────────────────────────────────────────────────────────────

    private function comparativeGroupsReport(array $filters, $groupIds): array
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

    private function comparativePeriodsReport(array $filters, $groupIds): array
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

        return ['periods' => array_values($periods), 'filters' => $filters];
    }
}
