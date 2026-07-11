<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use Illuminate\Http\Request;

class DashboardSummaryApiController extends Controller
{
    public function lastMeeting(Request $request)
    {
        $groupId = $request->validate(['group_id' => 'required|integer'])['group_id'];

        $meeting = Meeting::where('group_id', $groupId)
            ->where('status', 'closed')
            ->orderBy('meeting_number', 'desc')
            ->first();

        if (!$meeting) {
            return response()->json([
                'meeting_number' => null,
                'meeting_date'   => null,
                'savings'        => 0,
                'emergency'      => 0,
                'fines'          => 0,
            ]);
        }

        return response()->json([
            'meeting_number' => $meeting->meeting_number,
            'meeting_date'   => $meeting->meeting_date->format('Y-m-d'),
            'savings'        => (float) $meeting->total_savings,
            'emergency'      => (float) $meeting->total_emergency,
            'fines'          => (float) $meeting->total_fines,
        ]);
    }
}
