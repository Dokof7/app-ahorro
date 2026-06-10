<?php

namespace App\Http\Controllers;

use App\Models\BankExpense;
use App\Models\Group;
use App\Models\Member;
use App\Models\Meeting;
use App\Models\Loan;
use App\Models\MeetingContribution;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->isMiembro()) {
            return redirect()->route('portal.contributions');
        }

        $query = $user->isAdmin() ? Group::query() : $user->groups();

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

        $chartData = $this->getChartData($groupIds);

        return view('dashboard.index', compact('stats', 'chartData', 'groups'));
    }

    private function getChartData($groupIds)
    {
        $months = collect(range(5, 0))->map(fn($i) => now()->subMonths($i)->format('Y-m'));

        $savingsByMonth = [];
        $emergencyByMonth = [];
        $labels = [];

        foreach ($months as $month) {
            [$year, $m] = explode('-', $month);
            $labels[] = \Carbon\Carbon::createFromDate($year, $m, 1)->translatedFormat('M Y');

            $contributions = MeetingContribution::whereHas('meeting', function ($q) use ($groupIds, $year, $m) {
                $q->whereIn('group_id', $groupIds)
                  ->whereYear('meeting_date', $year)
                  ->whereMonth('meeting_date', $m);
            });

            $savingsByMonth[] = $contributions->sum('savings');
            $emergencyByMonth[] = $contributions->sum('emergency_fund');
        }

        return [
            'labels'    => $labels,
            'savings'   => $savingsByMonth,
            'emergency' => $emergencyByMonth,
        ];
    }
}
