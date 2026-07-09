<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use Illuminate\Http\Request;

class MeetingTotalController extends Controller
{
    public function update(Request $request, Meeting $meeting)
    {
        $this->authorize('update', $meeting);

        abort_unless($meeting->group->isPartial(), 403);

        if ($meeting->isClosed()) {
            return back()->with('error', 'Reunión cerrada.');
        }

        $data = $request->validate([
            'shares'         => 'required|integer|min:0',
            'emergency_fund' => 'required|numeric|min:0',
            'fine'           => 'required|numeric|min:0',
            'observations'   => 'nullable|string',
        ]);

        $meeting->totals()->updateOrCreate(['meeting_id' => $meeting->id], $data);
        $meeting->recalculateSummary();

        return back()->with('success', 'Totales de la reunión actualizados.');
    }
}
