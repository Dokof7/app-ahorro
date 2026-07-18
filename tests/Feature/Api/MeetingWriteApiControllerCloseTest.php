<?php

namespace Tests\Feature\Api;

use App\Models\Meeting;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesGroupFixtures;
use Tests\TestCase;

class MeetingWriteApiControllerCloseTest extends TestCase
{
    use RefreshDatabase;
    use CreatesGroupFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function makeOpenMeeting($group): Meeting
    {
        return $this->makeMeeting($group, [
            'meeting_number' => 1,
            'meeting_date' => '2026-07-16',
            'month' => 'Julio',
            'status' => 'open',
        ]);
    }

    // ---- Authorization: 403 negatives ----

    public function test_observador_role_is_forbidden(): void
    {
        $group = $this->makeGroup();
        $meeting = $this->makeOpenMeeting($group);
        $user = $this->makeUserWithRole('observador', $group);

        $response = $this->actingAs($user)->postJson("/api/meetings/{$meeting->id}/close");

        $response->assertStatus(403);
        $response->assertJsonPath('reason', 'role');
        $this->assertEquals('open', $meeting->fresh()->status);
    }

    public function test_tesorero_of_another_group_is_forbidden(): void
    {
        $groupA = $this->makeGroup();
        $groupB = $this->makeGroup();
        $meeting = $this->makeOpenMeeting($groupB);
        $user = $this->makeUserWithRole('tesorero', $groupA);

        $response = $this->actingAs($user)->postJson("/api/meetings/{$meeting->id}/close");

        $response->assertStatus(403);
        $response->assertJsonPath('reason', 'role');
        $this->assertEquals('open', $meeting->fresh()->status);
    }

    public function test_closing_an_already_closed_meeting_returns_the_closed_shape(): void
    {
        $group = $this->makeGroup();
        $meeting = $this->makeMeeting($group, [
            'meeting_number' => 1,
            'meeting_date' => '2026-07-16',
            'month' => 'Julio',
            'status' => 'closed',
        ]);
        $user = $this->makeUserWithRole('tesorero', $group);

        $response = $this->actingAs($user)->postJson("/api/meetings/{$meeting->id}/close");

        $response->assertStatus(403);
        $response->assertJsonPath('reason', 'closed');
    }

    // ---- 200: happy path ----

    public function test_tesorero_closes_the_open_meeting(): void
    {
        $group = $this->makeGroup();
        $meeting = $this->makeOpenMeeting($group);
        $user = $this->makeUserWithRole('tesorero', $group);

        $response = $this->actingAs($user)->postJson("/api/meetings/{$meeting->id}/close");

        $response->assertStatus(200);
        $response->assertJsonPath('meeting.id', $meeting->id);
        $response->assertJsonPath('meeting.status', 'closed');
        $this->assertEquals('closed', $meeting->fresh()->status);
    }

    public function test_admin_can_close_without_group_membership(): void
    {
        $group = $this->makeGroup();
        $meeting = $this->makeOpenMeeting($group);
        $user = $this->makeUserWithRole('admin');

        $response = $this->actingAs($user)->postJson("/api/meetings/{$meeting->id}/close");

        $response->assertStatus(200);
        $this->assertEquals('closed', $meeting->fresh()->status);
    }
}
