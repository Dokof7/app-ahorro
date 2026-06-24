<?php

namespace App\Http\Controllers;

use App\Models\MeetingScheduledDate;
use App\Models\Group;
use Illuminate\Http\Request;

class MeetingScheduledDateController extends Controller
{
    public function index(Request $request)
    {
        $groupId = $request->group_id;

        $dates = MeetingScheduledDate::where('group_id', $groupId)
            ->orderBy('scheduled_date')
            ->get(['id', 'scheduled_date', 'notes', 'used']);

        return response()->json($dates);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'group_id'       => 'required|exists:groups,id',
            'scheduled_date' => 'required|date',
            'notes'          => 'nullable|string|max:200',
        ]);

        $date = MeetingScheduledDate::firstOrCreate(
            ['group_id' => $data['group_id'], 'scheduled_date' => $data['scheduled_date']],
            ['notes' => $data['notes'] ?? null]
        );

        return response()->json($date, 201);
    }

    public function destroy(MeetingScheduledDate $scheduledDate)
    {
        $scheduledDate->delete();
        return response()->json(['ok' => true]);
    }

    public function next(Request $request)
    {
        $groupId = $request->validate(['group_id' => 'required|exists:groups,id'])['group_id'];

        $next = MeetingScheduledDate::where('group_id', $groupId)
            ->where('used', false)
            ->where('scheduled_date', '>=', now()->toDateString())
            ->orderBy('scheduled_date')
            ->first();

        return response()->json(['date' => $next?->scheduled_date?->format('Y-m-d')]);
    }
}
