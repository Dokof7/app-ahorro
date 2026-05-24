<?php

namespace App\Http\Controllers;

use App\Models\MeetingContribution;
use App\Models\Meeting;
use Illuminate\Http\Request;

class ContributionController extends Controller
{
    public function update(Request $request, Meeting $meeting, MeetingContribution $contribution)
    {
        $this->authorize('update', $meeting);

        if ($meeting->isClosed()) {
            return response()->json(['error' => 'Reunión cerrada, no se puede editar.'], 403);
        }

        $data = $request->validate([
            'shares'         => 'required|integer|min:1|max:25',
            'emergency_fund' => 'nullable|numeric|min:0',
            'fine'           => 'nullable|numeric|min:0',
            'confirmed'      => 'boolean',
            'observations'   => 'nullable|string',
        ]);

        $data['emergency_fund'] = $data['emergency_fund'] ?? 0;
        $data['fine']           = $data['fine'] ?? 0;

        $contribution->update($data);
        $meeting->recalculateSummary();

        return response()->json([
            'success' => true,
            'total'   => number_format($contribution->fresh()->total, 2),
            'summary' => $meeting->fresh()->load('summary')->summary,
        ]);
    }

    public function bulkUpdate(Request $request, Meeting $meeting)
    {
        $this->authorize('update', $meeting);

        if ($meeting->isClosed()) {
            return back()->with('error', 'Reunión cerrada.');
        }

        $data = $request->validate([
            'contributions'                  => 'required|array',
            'contributions.*.id'             => 'required|exists:meeting_contributions,id',
            'contributions.*.shares'         => 'required|integer|min:1|max:25',
            'contributions.*.emergency_fund' => 'nullable|numeric|min:0',
            'contributions.*.fine'           => 'nullable|numeric|min:0',
            'contributions.*.confirmed'      => 'boolean',
        ]);

        $shareValue = $meeting->group->share_value ?? 10;
        foreach ($data['contributions'] as $item) {
            $savings   = $item['shares'] * $shareValue;
            $emergency = $item['emergency_fund'] ?? 0;
            $fine      = $item['fine'] ?? 0;
            MeetingContribution::where('id', $item['id'])
                ->where('meeting_id', $meeting->id)
                ->update([
                    'shares'         => $item['shares'],
                    'savings'        => $savings,
                    'emergency_fund' => $emergency,
                    'fine'           => $fine,
                    'confirmed'      => $item['confirmed'] ?? false,
                    'total'          => $savings + $emergency + $fine,
                ]);
        }

        $meeting->recalculateSummary();
        return back()->with('success', 'Aportes actualizados exitosamente.');
    }
}
