<?php

namespace Tests\Feature\Actions;

use App\Actions\OpenMeetingForGroup;
use App\Models\Attendance;
use App\Models\MeetingContribution;
use App\Models\MeetingScheduledDate;
use App\Models\MeetingTotal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesGroupFixtures;
use Tests\TestCase;

class OpenMeetingForGroupTest extends TestCase
{
    use RefreshDatabase;
    use CreatesGroupFixtures;

    public function test_full_group_seeds_attendance_and_contributions_per_member(): void
    {
        $group = $this->makeGroup(['registration_mode' => 'full']);
        $this->makeMember($group, ['full_name' => 'Miembro Uno']);
        $this->makeMember($group, ['full_name' => 'Miembro Dos']);
        $this->makeMember($group, ['full_name' => 'Miembro Tres']);

        $action = new OpenMeetingForGroup();
        $meeting = $action($group, '2026-07-16', 'Julio');

        $this->assertEquals(1, $meeting->meeting_number);
        $this->assertEquals('open', $meeting->status);
        $this->assertEquals(3, Attendance::where('meeting_id', $meeting->id)->count());
        $this->assertEquals(3, MeetingContribution::where('meeting_id', $meeting->id)->count());
        $this->assertEquals(0, MeetingTotal::where('meeting_id', $meeting->id)->count());
        $this->assertNotNull($meeting->fresh()->summary);
    }

    public function test_meeting_number_is_max_plus_one(): void
    {
        $group = $this->makeGroup(['registration_mode' => 'full']);
        $this->makeMeeting($group, ['meeting_number' => 5, 'status' => 'closed']);

        $action = new OpenMeetingForGroup();
        $meeting = $action($group, '2026-07-16', 'Julio');

        $this->assertEquals(6, $meeting->meeting_number);
    }

    public function test_marks_matching_scheduled_date_as_used(): void
    {
        // NOTE: this "opportunistic exact match" (`where('scheduled_date',
        // $meetingDate)`) is copied verbatim from the current web
        // MeetingController::store() to preserve behavior exactly (per
        // design ADR-1/ADR-2). Under MySQL a DATE column compares directly
        // against a "Y-m-d" string. Under sqlite (this test's driver), a
        // `date`-cast column is persisted as "Y-m-d 00:00:00", so a raw
        // string equality against "Y-m-d" never matches — a pre-existing
        // sqlite-only quirk of the copied query, not a regression introduced
        // by the Action. This test exercises the SAME query the Action runs
        // by inserting the scheduled_date in the sqlite-stored shape so the
        // match condition (and therefore the update) is actually exercised.
        $group = $this->makeGroup(['registration_mode' => 'full']);
        $scheduled = MeetingScheduledDate::create([
            'group_id' => $group->id,
            'scheduled_date' => '2026-07-16',
            'used' => false,
        ]);
        $storedValue = \DB::table('meeting_scheduled_dates')->where('id', $scheduled->id)->value('scheduled_date');

        $action = new OpenMeetingForGroup();
        $action($group, $storedValue, 'Julio');

        $this->assertTrue($scheduled->fresh()->used);
    }

    public function test_partial_group_seeds_single_zero_total_and_no_contributions(): void
    {
        $group = $this->makeGroup(['registration_mode' => 'partial']);
        $this->makeMember($group, ['full_name' => 'Miembro Uno']);
        $this->makeMember($group, ['full_name' => 'Miembro Dos']);
        $this->makeMember($group, ['full_name' => 'Miembro Tres']);

        $action = new OpenMeetingForGroup();
        $meeting = $action($group, '2026-07-16', 'Julio');

        $this->assertEquals(3, Attendance::where('meeting_id', $meeting->id)->count());
        $this->assertEquals(0, MeetingContribution::where('meeting_id', $meeting->id)->count());

        $total = MeetingTotal::where('meeting_id', $meeting->id)->first();
        $this->assertNotNull($total);
        $this->assertEquals(0, $total->shares);
        $this->assertEquals(0, $total->emergency_fund);
        $this->assertEquals(0, $total->fine);
    }

    public function test_accepts_optional_status_param_defaulting_to_open(): void
    {
        $group = $this->makeGroup(['registration_mode' => 'full']);

        $action = new OpenMeetingForGroup();
        $meeting = $action($group, '2026-07-16', 'Julio', null, 'closed');

        $this->assertEquals('closed', $meeting->status);
    }
}
