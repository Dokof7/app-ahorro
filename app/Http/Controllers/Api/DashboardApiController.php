<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankExpense;
use App\Models\Group;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Meeting;
use App\Models\MeetingContribution;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = $user->isAdmin() ? Group::query() : $user->groups();

        $groups   = $query->with(['members', 'meetings'])->get();
        $groupIds = $groups->pluck('id');

        $stats = [
            'total_groups'          => $groups->count(),
            'total_members'         => Member::whereIn('group_id', $groupIds)->count(),
            'total_meetings'        => Meeting::whereIn('group_id', $groupIds)->count(),
            'total_savings'         => (float) MeetingContribution::whereHas('meeting', fn($q) => $q->whereIn('group_id', $groupIds))->sum('savings'),
            'total_emergency'       => (float) MeetingContribution::whereHas('meeting', fn($q) => $q->whereIn('group_id', $groupIds))->sum('emergency_fund'),
            'total_fines'           => (float) MeetingContribution::whereHas('meeting', fn($q) => $q->whereIn('group_id', $groupIds))->sum('fine'),
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

        $chartData = $this->chartData($groupIds);

        $groupList = $groups->map(fn($g) => [
            'id'          => $g->id,
            'name'        => $g->name,
            'description' => $g->description,
            'status'      => $g->status,
            'members'     => $g->members->count(),
            'meetings'    => $g->meetings->count(),
        ]);

        return response()->json([
            'stats'     => $stats,
            'chart'     => $chartData,
            'groups'    => $groupList,
        ]);
    }

    private function chartData($groupIds): array
    {
        $months  = collect(range(5, 0))->map(fn($i) => now()->subMonths($i)->format('Y-m'));
        $labels  = [];
        $savings = [];
        $emergency = [];

        foreach ($months as $month) {
            [$year, $m] = explode('-', $month);
            $labels[]    = Carbon::createFromDate($year, $m, 1)->format('M Y');

            $base = MeetingContribution::whereHas('meeting', fn($q) =>
                $q->whereIn('group_id', $groupIds)
                  ->whereYear('meeting_date', $year)
                  ->whereMonth('meeting_date', $m)
            );

            $savings[]   = (float) (clone $base)->sum('savings');
            $emergency[] = (float) (clone $base)->sum('emergency_fund');
        }

        return compact('labels', 'savings', 'emergency');
    }
}
