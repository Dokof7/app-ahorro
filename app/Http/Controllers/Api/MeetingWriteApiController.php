<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesMeetingWrite;
use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;

class MeetingWriteApiController extends Controller
{
    use AuthorizesMeetingWrite;

    /**
     * Return the open meeting for the given group, pre-seeded with its
     * contribution/attendance rows, so mobile clients can populate write
     * forms in one call.
     */
    public function open(Request $request)
    {
        $data = $request->validate([
            'group_id' => 'required|exists:groups,id',
        ]);

        $group = Group::findOrFail($data['group_id']);
        $user = $request->user();

        $this->denyUnlessRole(
            $user->isAdmin() || $user->groups()->where('groups.id', $group->id)->exists()
        );

        $meeting = $group->meetings()
            ->where('status', 'open')
            ->with(['contributions.member', 'attendances.member', 'totals'])
            ->first();

        if (!$meeting) {
            return response()->json([
                'meeting' => null,
                'is_partial' => $group->isPartial(),
                'contributions' => [],
                'attendances' => [],
                'totals' => null,
            ]);
        }

        return response()->json([
            'meeting' => $meeting,
            'is_partial' => $group->isPartial(),
            'contributions' => $meeting->contributions,
            'attendances' => $meeting->attendances,
            'totals' => $meeting->totals,
        ]);
    }
}
