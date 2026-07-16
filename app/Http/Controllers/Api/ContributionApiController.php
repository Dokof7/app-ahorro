<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesMeetingWrite;
use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\MeetingContribution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContributionApiController extends Controller
{
    use AuthorizesMeetingWrite;

    public function bulkStore(Request $request, Meeting $meeting)
    {
        $this->assertCanWriteMeeting($meeting);

        $isPartial = $meeting->group->isPartial();

        if ($isPartial) {
            $data = $request->validate([
                'shares' => 'required|integer|min:0',
                'emergency_fund' => 'nullable|numeric|min:0',
                'fine' => 'nullable|numeric|min:0',
                'observations' => 'nullable|string',
            ]);
        } else {
            $data = $request->validate([
                'contributions' => 'required|array',
                'contributions.*.id' => 'required|exists:meeting_contributions,id',
                'contributions.*.shares' => 'required|integer|min:0|max:25',
                'contributions.*.emergency_fund' => 'nullable|numeric|min:0',
                'contributions.*.fine' => 'nullable|numeric|min:0',
                'contributions.*.confirmed' => 'boolean',
            ]);
        }

        DB::transaction(function () use ($meeting, $data, $isPartial) {
            $meeting = Meeting::whereKey($meeting->id)->lockForUpdate()->firstOrFail();

            if ($isPartial) {
                $meeting->totals()->updateOrCreate(['meeting_id' => $meeting->id], [
                    'shares' => $data['shares'],
                    'emergency_fund' => $data['emergency_fund'] ?? 0,
                    'fine' => $data['fine'] ?? 0,
                    'observations' => $data['observations'] ?? null,
                ]);
            } else {
                foreach ($data['contributions'] as $item) {
                    $contribution = MeetingContribution::where('id', $item['id'])
                        ->where('meeting_id', $meeting->id)
                        ->firstOrFail();

                    $contribution->update([
                        'shares' => $item['shares'],
                        'emergency_fund' => $item['emergency_fund'] ?? 0,
                        'fine' => $item['fine'] ?? 0,
                        'confirmed' => $item['confirmed'] ?? false,
                    ]);
                }
            }

            $meeting->recalculateSummary();
        });

        $meeting = $meeting->fresh()->load('summary', 'totals');

        return response()->json([
            'summary' => $meeting->summary,
            'totals' => $isPartial ? $meeting->totals : $meeting->contributions()->get(),
        ]);
    }
}
