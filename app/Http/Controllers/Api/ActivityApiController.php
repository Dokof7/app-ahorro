<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesMeetingWrite;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Group;
use Illuminate\Http\Request;

class ActivityApiController extends Controller
{
    use AuthorizesMeetingWrite;

    public function index(Request $request, Group $group)
    {
        $this->authorizeGroup($request, $group);

        return response()->json([
            'data' => $group->activities()->orderBy('activity_date', 'desc')->get(),
        ]);
    }

    public function store(Request $request, Group $group)
    {
        $this->authorizeGroupWrite($request, $group);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'activity_date' => 'required|date',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'amount_raised' => 'nullable|numeric|min:0',
        ]);

        $data['group_id'] = $group->id;
        $activity = Activity::create($data);

        return response()->json(['data' => $activity], 201);
    }

    public function update(Request $request, Activity $activity)
    {
        $this->authorizeGroupWrite($request, $activity->group);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'activity_date' => 'required|date',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'amount_raised' => 'nullable|numeric|min:0',
        ]);

        $activity->update($data);

        return response()->json(['data' => $activity->fresh()]);
    }

    public function destroy(Request $request, Activity $activity)
    {
        $this->authorizeGroupWrite($request, $activity->group);

        $activity->delete();

        return response()->json(['success' => true]);
    }

    private function authorizeGroup(Request $request, Group $group): void
    {
        $user = $request->user();
        $this->denyUnlessRole(
            $user->isAdmin() || $user->groups()->where('groups.id', $group->id)->exists()
        );
    }

    private function authorizeGroupWrite(Request $request, Group $group): void
    {
        $user = $request->user();
        $this->denyUnlessRole(
            $user->isAdmin() || ($user->canEdit() && $user->groups()->where('groups.id', $group->id)->exists())
        );
    }
}
