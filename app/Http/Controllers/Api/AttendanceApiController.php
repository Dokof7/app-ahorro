<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesMeetingWrite;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AttendanceApiController extends Controller
{
    use AuthorizesMeetingWrite;

    public function bulkUpdate(Request $request, Meeting $meeting)
    {
        $this->assertCanWriteMeeting($meeting);

        $data = $request->validate([
            'attendances' => 'required|array',
            'attendances.*.id' => [
                'required',
                Rule::exists('attendances', 'id')->where('meeting_id', $meeting->id),
            ],
            'attendances.*.status' => 'required|in:present,late,absent,excused',
            'attendances.*.observations' => 'nullable|string',
        ]);

        DB::transaction(function () use ($meeting, $data) {
            Meeting::whereKey($meeting->id)->lockForUpdate()->firstOrFail();

            foreach ($data['attendances'] as $item) {
                Attendance::where('id', $item['id'])
                    ->where('meeting_id', $meeting->id)
                    ->update([
                        'status' => $item['status'],
                        'observations' => $item['observations'] ?? null,
                    ]);
            }
        });

        return response()->json([
            'success' => true,
            'attendances' => $meeting->fresh()->attendances,
        ]);
    }
}
