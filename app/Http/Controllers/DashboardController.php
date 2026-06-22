<?php

namespace App\Http\Controllers;

use App\Models\BankExpense;
use App\Models\Group;
use App\Models\Member;
use App\Models\Meeting;
use App\Models\Loan;
use App\Models\MeetingContribution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->isMiembro()) {
            return redirect()->route('portal.contributions');
        }

        if ($user->isAdmin()) {
            $activeGroupId = session('active_group_id');
            $query = Group::where('id', $activeGroupId);
        } else {
            $query = $user->groups();
        }

        $groups = $query->with(['members', 'meetings'])->get();
        $groupIds = $groups->pluck('id');

        $stats = [
            'total_groups'       => $groups->count(),
            'total_members'      => Member::whereIn('group_id', $groupIds)->count(),
            'total_meetings'     => Meeting::whereIn('group_id', $groupIds)->count(),
            'total_savings'      => MeetingContribution::whereHas('meeting', fn($q) => $q->whereIn('group_id', $groupIds))->sum('savings'),
            'total_emergency'    => MeetingContribution::whereHas('meeting', fn($q) => $q->whereIn('group_id', $groupIds))->sum('emergency_fund'),
            'total_fines'        => MeetingContribution::whereHas('meeting', fn($q) => $q->whereIn('group_id', $groupIds))->sum('fine'),
            'loans_pending'      => Loan::whereIn('group_id', $groupIds)->where('status', 'pending')->sum('balance'),
            'loans_paid'         => Loan::whereIn('group_id', $groupIds)->where('status', 'paid')->sum('total_to_return'),
            'loans_overdue'         => Loan::whereIn('group_id', $groupIds)->where('status', 'overdue')->count(),
            'loans_overdue_balance' => Loan::whereIn('group_id', $groupIds)->where('status', 'overdue')->sum('balance'),
            'bank_expenses'         => BankExpense::whereIn('group_id', $groupIds)->sum('amount'),
            'total_membership'      => Member::whereIn('group_id', $groupIds)
                                        ->where('membership_paid', true)
                                        ->join('groups', 'members.group_id', '=', 'groups.id')
                                        ->sum('groups.membership_fee'),
        ];

        $chartData   = $this->getChartData($groupIds);
        $sharesChart = $this->getSharesChart($groupIds);

        return view('dashboard.index', compact('stats', 'chartData', 'sharesChart', 'groups'));
    }

    private function getChartData($groupIds)
    {
        $meetings = Meeting::whereIn('group_id', $groupIds)
            ->orderByDesc('meeting_date')
            ->limit(4)
            ->get(['id', 'meeting_number', 'meeting_date'])
            ->reverse()
            ->values();

        $labels   = [];
        $savings  = [];
        $emergency = [];

        foreach ($meetings as $meeting) {
            $labels[]    = 'Reunión ' . $meeting->meeting_number . ' (' . \Carbon\Carbon::parse($meeting->meeting_date)->format('d/m/Y') . ')';
            $savings[]   = (float) MeetingContribution::where('meeting_id', $meeting->id)->sum('savings');
            $emergency[] = (float) MeetingContribution::where('meeting_id', $meeting->id)->sum('emergency_fund');
        }

        return compact('labels', 'savings', 'emergency');
    }

    private function getSharesChart($groupIds)
    {
        $lastMeetingIds = Meeting::whereIn('group_id', $groupIds)
            ->orderByDesc('meeting_date')
            ->limit(4)
            ->pluck('id');

        $rows = MeetingContribution::whereIn('meeting_id', $lastMeetingIds)
            ->join('members', 'meeting_contributions.member_id', '=', 'members.id')
            ->select('members.name', DB::raw('SUM(meeting_contributions.shares) as total_shares'))
            ->groupBy('members.id', 'members.name')
            ->orderByDesc('total_shares')
            ->get();

        return [
            'labels' => $rows->pluck('name')->toArray(),
            'data'   => $rows->pluck('total_shares')->map(fn($v) => (float) $v)->toArray(),
        ];
    }
}
