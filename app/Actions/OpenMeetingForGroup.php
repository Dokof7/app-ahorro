<?php

namespace App\Actions;

use App\Models\Attendance;
use App\Models\Group;
use App\Models\Meeting;
use App\Models\MeetingContribution;
use App\Models\MeetingScheduledDate;
use App\Models\MeetingTotal;
use App\Models\Member;
use Carbon\Carbon;

/**
 * Opens a meeting for a group: computes the next meeting_number, creates the
 * Meeting row, opportunistically flips a matching scheduled date to used,
 * auto-seeds Attendance + (MeetingContribution per member | one MeetingTotal)
 * depending on the group's registration mode, and recalculates the summary.
 *
 * This is the single seeding path shared by the web `MeetingController::store`
 * and the mobile `POST /groups/{group}/meetings` endpoint — the underlying
 * contribution/attendance math lives in model events (see MeetingContribution,
 * Attendance, Meeting::recalculateSummary), NOT here.
 *
 * Callers are responsible for wrapping the call in a DB transaction with a
 * row lock over the group's meetings (see ADR-3) so meeting_number=max+1 and
 * the single-open-meeting invariant stay race-free; this Action does not open
 * its own transaction.
 */
class OpenMeetingForGroup
{
    public function __invoke(
        Group $group,
        string $meetingDate,
        string $month,
        ?string $observations = null,
        string $status = 'open'
    ): Meeting {
        $meetingNumber = $group->meetings()->max('meeting_number') + 1;

        $meeting = Meeting::create([
            'group_id' => $group->id,
            'meeting_number' => $meetingNumber,
            'meeting_date' => $meetingDate,
            'month' => $month,
            'observations' => $observations,
            'status' => $status,
        ]);

        // Mark the scheduled date as used if it matches (opportunistic exact
        // match; no-op if none matches — preserves current web behavior).
        MeetingScheduledDate::where('group_id', $group->id)
            ->where('scheduled_date', $meetingDate)
            ->update(['used' => true]);

        $members = Member::where('group_id', $group->id)->where('status', 'active')->get();
        $isPartial = $group->isPartial();

        foreach ($members as $member) {
            Attendance::create(['meeting_id' => $meeting->id, 'member_id' => $member->id]);
            if (!$isPartial) {
                MeetingContribution::create([
                    'meeting_id' => $meeting->id,
                    'member_id' => $member->id,
                    'shares' => 0,
                    'emergency_fund' => 0,
                    'fine' => 0,
                    'confirmed' => false,
                ]);
            }
        }

        if ($isPartial) {
            MeetingTotal::create([
                'meeting_id' => $meeting->id,
                'shares' => 0,
                'emergency_fund' => 0,
                'fine' => 0,
            ]);
        }

        $meeting->recalculateSummary();

        return $meeting;
    }

    /**
     * Spanish month name for the given date, matching the literal array used
     * by the web view's JS (`meetings/create.blade.php`) EXACTLY so both
     * paths persist byte-identical `month` strings. Deliberately NOT using
     * Carbon's locale translation (ext-intl dependent, may yield different
     * casing/accents than the existing DB values).
     */
    public static function spanishMonth(string $date): string
    {
        $meses = [
            'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
        ];

        return $meses[Carbon::parse($date)->month - 1];
    }
}
