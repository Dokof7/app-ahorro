<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Member;
use Illuminate\Http\Request;

class AdminApiController extends Controller
{
    /**
     * GET /api/admin/groups
     * Groups in the user's scope with member/meeting counts.
     */
    public function groups(Request $request)
    {
        $user = $request->user();
        if (!$user->isAdmin() && !$user->isAdminGrupo()) {
            abort(403);
        }

        $groupIds = $this->resolveGroupIds($user);

        $groups = Group::whereIn('id', $groupIds)
            ->withCount(['members', 'meetings'])
            ->orderBy('name')
            ->get();

        $data = $groups->map(fn($group) => [
            'id'          => $group->id,
            'name'        => $group->name,
            'description' => $group->description ?? '',
            'status'      => $group->status,
            'share_value' => (float) $group->share_value,
            'start_date'  => $group->start_date?->format('Y-m-d'),
            'members'     => $group->members_count,
            'meetings'    => $group->meetings_count,
        ])->values();

        return response()->json(['data' => $data]);
    }

    /**
     * GET /api/admin/groups/{group}/members
     * Active members of a group with savings, fines and attendance aggregates.
     */
    public function members(Request $request, Group $group)
    {
        $user = $request->user();
        if (!$user->isAdmin() && !$user->isAdminGrupo()) {
            abort(403);
        }

        if (!$this->resolveGroupIds($user)->contains($group->id)) {
            abort(403);
        }

        // Aliases avoid Member::getTotalSavingsAttribute()/getTotalEmergencyAttribute(),
        // which would shadow same-named withSum columns and re-query per member (N+1).
        $members = Member::where('group_id', $group->id)
            ->where('status', 'active')
            ->withSum('contributions as savings_sum', 'savings')
            ->withSum('contributions as emergency_sum', 'emergency_fund')
            ->withSum('contributions as shares_sum', 'shares')
            ->withSum('fines as fines_sum', 'amount')
            ->withCount([
                'attendances as attended'         => fn($q) => $q->whereIn('status', ['present', 'late']),
                'attendances as absences'         => fn($q) => $q->where('status', 'absent'),
                'attendances as excused_absences' => fn($q) => $q->where('status', 'excused'),
            ])
            ->orderBy('full_name')
            ->get();

        $data = $members->map(fn($member) => [
            'id'               => $member->id,
            'full_name'        => $member->full_name,
            'document_number'  => $member->document_number,
            'phone'            => $member->phone,
            'status'           => $member->status,
            'membership_paid'  => (bool) $member->membership_paid,
            'join_date'        => $member->join_date?->format('Y-m-d'),
            'total_savings'    => (float) ($member->savings_sum ?? 0),
            'total_emergency'  => (float) ($member->emergency_sum ?? 0),
            'total_fines'      => (float) ($member->fines_sum ?? 0),
            'total_shares'     => (int) ($member->shares_sum ?? 0),
            'attended'         => $member->attended,
            'absences'         => $member->absences,
            'excused_absences' => $member->excused_absences,
        ])->values();

        return response()->json(['data' => $data]);
    }

    private function resolveGroupIds($user)
    {
        return $user->isAdmin() ? Group::pluck('id') : $user->groups()->pluck('groups.id');
    }
}
