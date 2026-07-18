<?php

namespace App\Http\Controllers\Api;

use App\Actions\OpenMeetingForGroup;
use App\Http\Controllers\Api\Concerns\AuthorizesMeetingWrite;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        return response()->json($this->meetingPayload($meeting, $group));
    }

    /**
     * Open (create) a new meeting for the given group. Enforces a single
     * open meeting per group via a transaction + row lock over the group's
     * meetings (design ADR-3), derives `month` server-side (ADR-4, ignoring
     * any client-supplied value), and delegates seeding to
     * OpenMeetingForGroup — the same Action the web store() route uses.
     */
    public function store(Request $request, Group $group, OpenMeetingForGroup $openMeetingForGroup)
    {
        $data = $request->validate([
            'meeting_date' => 'required|date',
        ]);

        $user = $request->user();

        // No Meeting exists yet to authorize via MeetingPolicy::update, so we
        // mirror that policy's role matrix here directly. Keep this
        // predicate in sync with MeetingPolicy::update() if that ever
        // changes.
        $this->denyUnlessRole(
            $user->isAdmin()
                || ($user->canEdit() && $user->groups()->where('groups.id', $group->id)->exists())
        );

        $month = OpenMeetingForGroup::spanishMonth($data['meeting_date']);

        [$status, $payload] = DB::transaction(function () use ($group, $data, $month, $openMeetingForGroup) {
            // Row lock over the group's meetings serializes concurrent
            // openers so the open-meeting check and meeting_number=max+1
            // can't race (design ADR-3).
            $group->meetings()->lockForUpdate()->get();

            $existingOpen = $group->meetings()->where('status', 'open')->first();

            if ($existingOpen) {
                return [409, [
                    'error' => 'Ya hay una reunión abierta para este grupo.',
                    'reason' => 'meeting_already_open',
                    'meeting' => [
                        'id' => $existingOpen->id,
                        'meeting_number' => $existingOpen->meeting_number,
                        'meeting_date' => $existingOpen->meeting_date->toDateString(),
                        'month' => $existingOpen->month,
                        'is_partial' => $group->isPartial(),
                    ],
                ]];
            }

            $meeting = $openMeetingForGroup($group, $data['meeting_date'], $month);

            return [201, $this->meetingPayload($meeting, $group)];
        });

        return response()->json($payload, $status);
    }

    /**
     * Close the given meeting. Mirrors the web MeetingController::close():
     * same policy gate (via assertCanWriteMeeting, which also rejects
     * already-closed meetings with the unified 403 `closed` shape) and the
     * same summary recalculation.
     */
    public function close(Request $request, Meeting $meeting)
    {
        $this->assertCanWriteMeeting($meeting);

        $meeting->update(['status' => 'closed']);
        $meeting->recalculateSummary();

        return response()->json([
            'meeting' => [
                'id' => $meeting->id,
                'meeting_number' => $meeting->meeting_number,
                'status' => $meeting->status,
            ],
        ]);
    }

    /**
     * Shared response shape for a loaded/created meeting, reused by open()
     * and store() so both mobile entry points return byte-identical JSON
     * (design ADR-5) and the Flutter client can use one parser.
     */
    private function meetingPayload(Meeting $meeting, Group $group): array
    {
        $meeting->loadMissing(['contributions.member', 'attendances.member', 'totals']);

        return [
            'meeting' => $meeting,
            'is_partial' => $group->isPartial(),
            'contributions' => $meeting->contributions,
            'attendances' => $meeting->attendances,
            'totals' => $meeting->totals,
        ];
    }
}
