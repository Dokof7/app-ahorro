<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Meeting;
use App\Models\MeetingContribution;
use App\Models\MeetingTotal;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesGroupFixtures;
use Tests\TestCase;

/**
 * Non-regression test for the web MeetingController::store() route,
 * captured BEFORE refactoring it to delegate to OpenMeetingForGroup. Must
 * stay green, unmodified in assertions, after the refactor (design ADR-2).
 */
class MeetingControllerStoreTest extends TestCase
{
    use RefreshDatabase;
    use CreatesGroupFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_store_creates_meeting_and_redirects_for_full_group(): void
    {
        $group = $this->makeGroup(['registration_mode' => 'full']);
        $this->makeMember($group, ['full_name' => 'Miembro Uno']);
        $this->makeMember($group, ['full_name' => 'Miembro Dos']);
        $user = $this->makeUserWithRole('tesorero', $group);

        $response = $this->actingAs($user)->post('/meetings', [
            'group_id' => $group->id,
            'meeting_date' => '2026-07-16',
            'month' => 'Julio',
            'status' => 'open',
        ]);

        $meeting = Meeting::where('group_id', $group->id)->first();

        $this->assertNotNull($meeting);
        $response->assertRedirect(route('meetings.show', $meeting));
        $response->assertSessionHas('success', 'Reunión creada exitosamente.');

        $this->assertEquals(1, $meeting->meeting_number);
        $this->assertEquals('open', $meeting->status);
        $this->assertEquals(2, Attendance::where('meeting_id', $meeting->id)->count());
        $this->assertEquals(2, MeetingContribution::where('meeting_id', $meeting->id)->count());
        $this->assertEquals(0, MeetingTotal::where('meeting_id', $meeting->id)->count());
        $this->assertNotNull($meeting->fresh()->summary);
    }

    public function test_store_creates_meeting_with_single_total_for_partial_group(): void
    {
        $group = $this->makeGroup(['registration_mode' => 'partial']);
        $this->makeMember($group, ['full_name' => 'Miembro Uno']);
        $this->makeMember($group, ['full_name' => 'Miembro Dos']);
        $this->makeMember($group, ['full_name' => 'Miembro Tres']);
        $user = $this->makeUserWithRole('admin_grupo', $group);

        $response = $this->actingAs($user)->post('/meetings', [
            'group_id' => $group->id,
            'meeting_date' => '2026-07-16',
            'month' => 'Julio',
            'status' => 'open',
        ]);

        $meeting = Meeting::where('group_id', $group->id)->first();

        $this->assertNotNull($meeting);
        $response->assertRedirect(route('meetings.show', $meeting));
        $response->assertSessionHas('success', 'Reunión creada exitosamente.');

        $this->assertEquals(3, Attendance::where('meeting_id', $meeting->id)->count());
        $this->assertEquals(0, MeetingContribution::where('meeting_id', $meeting->id)->count());
        $this->assertEquals(1, MeetingTotal::where('meeting_id', $meeting->id)->count());
    }

    public function test_store_meeting_number_increments_from_previous(): void
    {
        $group = $this->makeGroup(['registration_mode' => 'full']);
        $this->makeMeeting($group, ['meeting_number' => 4, 'status' => 'closed']);
        $user = $this->makeUserWithRole('secretario', $group);

        $this->actingAs($user)->post('/meetings', [
            'group_id' => $group->id,
            'meeting_date' => '2026-07-16',
            'month' => 'Julio',
            'status' => 'open',
        ]);

        $meeting = Meeting::where('group_id', $group->id)->where('meeting_number', 5)->first();
        $this->assertNotNull($meeting);
    }

    public function test_store_is_forbidden_for_observador_role(): void
    {
        $group = $this->makeGroup(['registration_mode' => 'full']);
        $user = $this->makeUserWithRole('observador', $group);

        $response = $this->actingAs($user)->post('/meetings', [
            'group_id' => $group->id,
            'meeting_date' => '2026-07-16',
            'month' => 'Julio',
            'status' => 'open',
        ]);

        $response->assertStatus(403);
        $this->assertEquals(0, Meeting::where('group_id', $group->id)->count());
    }
}
