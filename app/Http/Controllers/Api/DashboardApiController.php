<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankExpense;
use App\Models\Group;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Meeting;
use App\Models\MeetingContribution;
use App\Models\MeetingTotal;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    public function index(Request $request)
    {
        $user     = $request->user();
        $groupIds = $this->resolveGroupIds($user);

        if ($groupIds->isEmpty()) {
            return response()->json([
                'stats'  => $this->emptyStats(),
                'chart'  => $this->emptyChart(),
                'groups' => [],
            ]);
        }

        $groups = Group::with(['members', 'meetings'])
            ->whereIn('id', $groupIds)
            ->get();

        // Contribution money lives in two tables depending on the group's
        // registration mode: per-member meeting_contributions (full) or a
        // single meeting_totals row (partial) — mirror Meeting's accessors
        // and sum both. A group only ever writes to one of them.
        $sumBoth = fn(string $column) =>
            (float) MeetingContribution::whereHas('meeting', fn($q) => $q->whereIn('group_id', $groupIds))->sum($column)
            + (float) MeetingTotal::whereHas('meeting', fn($q) => $q->whereIn('group_id', $groupIds))->sum($column);

        $stats = [
            'total_groups'          => $groups->count(),
            'total_members'         => Member::whereIn('group_id', $groupIds)->count(),
            'total_meetings'        => Meeting::whereIn('group_id', $groupIds)->count(),
            'total_savings'         => $sumBoth('savings'),
            'total_emergency'       => $sumBoth('emergency_fund'),
            'total_fines'           => $sumBoth('fine'),
            'loans_pending'         => (float) Loan::whereIn('group_id', $groupIds)->where('status', 'pending')->sum('balance'),
            'loans_paid'            => (float) Loan::whereIn('group_id', $groupIds)->where('status', 'paid')->sum('total_to_return'),
            'loans_overdue'         => Loan::whereIn('group_id', $groupIds)->where('status', 'overdue')->count(),
            'loans_overdue_balance' => (float) Loan::whereIn('group_id', $groupIds)->where('status', 'overdue')->sum('balance'),
            'bank_expenses'         => (float) BankExpense::whereIn('group_id', $groupIds)->sum('amount'),
            'total_membership'      => (float) Member::whereIn('group_id', $groupIds)
                                        ->where('membership_paid', true)
                                        ->join('groups', 'members.group_id', '=', 'groups.id')
                                        ->sum('groups.membership_fee'),
        ];

        $groupList = $groups->map(fn($g) => [
            'id'          => $g->id,
            'name'        => $g->name,
            'description' => $g->description,
            'status'      => $g->status,
            'members'     => $g->members->count(),
            'meetings'    => $g->meetings->count(),
        ]);

        return response()->json([
            'stats'  => $stats,
            'chart'  => $this->chartData($groupIds),
            'groups' => $groupList,
        ]);
    }

    private function resolveGroupIds($user)
    {
        // Admin ve todo
        if ($user->isAdmin()) {
            return Group::pluck('id');
        }

        // Miembro: su grupo viene de members.user_id
        if ($user->isMiembro()) {
            $member = Member::where('user_id', $user->id)->first();
            return $member ? collect([$member->group_id]) : collect();
        }

        // Tesorero, secretario, observador: grupos asignados via group_user
        return $user->groups()->pluck('groups.id');
    }

    private function chartData($groupIds): array
    {
        $months    = collect(range(5, 0))->map(fn($i) => now()->subMonths($i)->format('Y-m'));
        $labels    = [];
        $savings   = [];
        $emergency = [];

        foreach ($months as $month) {
            [$year, $m] = explode('-', $month);
            $labels[] = Carbon::createFromDate($year, $m, 1)->format('M Y');

            // Same dual-table rule as the stats: full groups write
            // meeting_contributions, partial groups write meeting_totals.
            $meetingFilter = fn($q) =>
                $q->whereIn('group_id', $groupIds)
                  ->whereYear('meeting_date', $year)
                  ->whereMonth('meeting_date', $m);

            $contribBase = MeetingContribution::whereHas('meeting', $meetingFilter);
            $totalsBase  = MeetingTotal::whereHas('meeting', $meetingFilter);

            $savings[]   = (float) (clone $contribBase)->sum('savings')
                         + (float) (clone $totalsBase)->sum('savings');
            $emergency[] = (float) (clone $contribBase)->sum('emergency_fund')
                         + (float) (clone $totalsBase)->sum('emergency_fund');
        }

        return compact('labels', 'savings', 'emergency');
    }

    private function emptyStats(): array
    {
        return [
            'total_groups' => 0, 'total_members' => 0, 'total_meetings' => 0,
            'total_savings' => 0, 'total_emergency' => 0, 'total_fines' => 0,
            'loans_pending' => 0, 'loans_paid' => 0, 'loans_overdue' => 0,
            'loans_overdue_balance' => 0, 'bank_expenses' => 0, 'total_membership' => 0,
        ];
    }

    private function emptyChart(): array
    {
        $labels = collect(range(5, 0))
            ->map(fn($i) => now()->subMonths($i)->format('M Y'))
            ->values()
            ->all();

        return ['labels' => $labels, 'savings' => array_fill(0, 6, 0), 'emergency' => array_fill(0, 6, 0)];
    }
}
