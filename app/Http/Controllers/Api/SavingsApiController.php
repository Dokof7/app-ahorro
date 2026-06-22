<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\Member;
use App\Models\MeetingContribution;
use Illuminate\Http\Request;

class SavingsApiController extends Controller
{
    public function index(Request $request)
    {
        $user   = $request->user();
        $member = Member::with('group')->where('user_id', $user->id)->first();

        if (!$member) {
            return response()->json([
                'member'        => null,
                'total'         => ['savings' => 0, 'emergency' => 0, 'fines' => 0, 'loans' => 0],
                'membership'    => ['paid' => false, 'paid_at' => null],
                'contributions' => [],
            ]);
        }

        $contributions = MeetingContribution::with('meeting')
            ->where('member_id', $member->id)
            ->orderBy('created_at', 'asc')
            ->get();

        $totalLoans = (float) Loan::where('member_id', $member->id)->sum('amount');

        $rows = $contributions->map(fn($c) => [
            'meeting_number' => $c->meeting?->meeting_number,
            'meeting_date'   => $c->meeting?->meeting_date?->format('d/m/Y'),
            'month'          => $c->meeting?->month,
            'shares'         => $c->shares,
            'savings'        => (float) $c->savings,
            'emergency'      => (float) $c->emergency_fund,
            'fines'          => (float) $c->fine,
            'total'          => (float) $c->total,
        ]);

        $total = [
            'savings'   => (float) $contributions->sum('savings'),
            'emergency' => (float) $contributions->sum('emergency_fund'),
            'fines'     => (float) $contributions->sum('fine'),
            'loans'     => $totalLoans,
        ];

        return response()->json([
            'member' => [
                'id'        => $member->id,
                'full_name' => $member->full_name,
                'group'     => $member->group?->name,
            ],
            'membership' => [
                'paid'    => (bool) $member->membership_paid,
                'paid_at' => $member->membership_paid_at?->format('d/m/Y'),
            ],
            'total'         => $total,
            'contributions' => $rows,
        ]);
    }
}
