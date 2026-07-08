<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Meeting;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function update(Request $request, Meeting $meeting)
    {
        $this->authorize('update', $meeting);

        if ($meeting->isClosed()) {
            return back()->with('error', 'Reunión cerrada.');
        }

        $data = $request->validate([
            'attendances'            => 'required|array',
            'attendances.*.id'       => 'required|exists:attendances,id',
            'attendances.*.status'   => 'required|in:present,late,absent,excused',
            'attendances.*.observations' => 'nullable|string',
        ]);

        foreach ($data['attendances'] as $item) {
            Attendance::where('id', $item['id'])
                ->where('meeting_id', $meeting->id)
                ->update([
                    'status'       => $item['status'],
                    'observations' => $item['observations'] ?? null,
                ]);
        }

        return back()->with('success', 'Asistencia guardada.');
    }
}
