<?php

namespace Tests\Feature\Api;

use App\Models\MeetingContribution;
use App\Models\Attendance;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesGroupFixtures;
use Tests\TestCase;

class MeetingWriteApiControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesGroupFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_returns_open_meeting_with_preseeded_contributions_and_attendance(): void
    {
        $group = $this->makeGroup();
        $meeting = $this->makeMeeting($group, ['status' => 'open']);
        $member = $this->makeMember($group);
        MeetingContribution::create(['meeting_id' => $meeting->id, 'member_id' => $member->id, 'shares' => 0]);
        Attendance::create(['meeting_id' => $meeting->id, 'member_id' => $member->id, 'status' => 'absent']);

        $user = $this->makeUserWithRole('tesorero', $group);

        $response = $this->actingAs($user)->getJson("/api/meetings/open?group_id={$group->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('meeting.id', $meeting->id);
        $response->assertJsonCount(1, 'contributions');
        $response->assertJsonCount(1, 'attendances');
    }

    public function test_no_open_meeting_returns_null(): void
    {
        $group = $this->makeGroup();
        $this->makeMeeting($group, ['status' => 'closed']);
        $user = $this->makeUserWithRole('tesorero', $group);

        $response = $this->actingAs($user)->getJson("/api/meetings/open?group_id={$group->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('meeting', null);
    }
}
