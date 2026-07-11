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

    /**
     * GET /api/reports/groups/{group}/summary
     * Optional query param: year.
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
            'year' => 'nullable|integer|min:2000|max:2100',
        ]);

        $periodsResult = $this->comparativeReportService->comparativePeriods(
            array_merge($filters, ['group_id' => $group->id]),
            $groupIds
        );

        $monthly = collect($periodsResult['periods'])->map(fn($period) => [
            'period'          => $period['period'],
            'label'           => $period['label'],
            'savings'         => (float) $period['savings'],
            'fines'           => (float) $period['fines'],
            'loans_out'       => (float) $period['loans_out'],
            'loan_payments'   => (float) $period['loan_payments'],
            'attendance_rate' => (float) $period['attendance_rate'],
            'savings_delta'   => $period['savings_delta'] !== null ? (float) $period['savings_delta'] : null,
        ])->values();

        $rankings = $this->comparativeReportService->memberRankings($group, $filters);

        $topSavers = collect($rankings['top_savers'])->map(fn($m) => [
            'member_id'     => $m['member_id'],
            'name'          => $m['name'],
            'total_saved'   => (float) $m['total_saved'],
            'contributions' => $m['contributions'],
        ])->values();

        $topAttendance = collect($rankings['top_attendance'])->map(fn($m) => [
            'member_id'       => $m['member_id'],
            'name'            => $m['name'],
            'attended'        => $m['attended'],
            'attendance_rate' => (float) $m['attendance_rate'],
        ])->values();

        return response()->json(['data' => [
            'group' => [
                'id'                => $group->id,
                'name'              => $group->name,
                'registration_mode' => $group->registration_mode ?? 'full',
            ],
            'monthly'        => $monthly,
            'top_savers'     => $topSavers,
            'top_attendance' => $topAttendance,
        ]]);
    }

    private function resolveGroupIds($user)
    {
        return $user->isAdmin() ? Group::pluck('id') : $user->groups()->pluck('groups.id');
    }
}
