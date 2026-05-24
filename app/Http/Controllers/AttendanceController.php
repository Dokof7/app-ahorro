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
            'attendances'                      => 'required|array',
            'attendances.*.id'                 => 'required|exists:attendances,id',
            'attendances.*.attended'           => 'boolean',
            'attendances.*.excused_absence'    => 'boolean',
            'attendances.*.paid_savings'       => 'boolean',
            'attendances.*.paid_emergency'     => 'boolean',
            'attendances.*.has_fine'           => 'boolean',
            'attendances.*.observations'       => 'nullable|string',
        ]);

        foreach ($data['attendances'] as $item) {
            $attended        = $item['attended'] ?? false;
            $excusedAbsence  = $item['excused_absence'] ?? false;
            // Si falta con permiso, no puede estar marcado como asistió
            if ($excusedAbsence) {
                $attended = false;
            }
            Attendance::where('id', $item['id'])
                ->where('meeting_id', $meeting->id)
                ->update([
                    'attended'        => $attended,
                    'excused_absence' => $excusedAbsence,
                    'paid_savings'    => $item['paid_savings'] ?? false,
                    'paid_emergency'  => $item['paid_emergency'] ?? false,
                    'has_fine'        => $item['has_fine'] ?? false,
                    'observations'    => $item['observations'] ?? null,
                ]);
        }

        return back()->with('success', 'Asistencia guardada.');
    }
}
