<?php

namespace Tests\Feature\Api;

use App\Models\Attendance;
use App\Models\Meeting;
use App\Models\MeetingContribution;
use App\Models\MeetingTotal;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesGroupFixtures;
use Tests\TestCase;

class MeetingWriteApiControllerStoreTest extends TestCase
{
    use RefreshDatabase;
    use CreatesGroupFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    // ---- Authorization: 403 negatives ----

    public function test_observador_role_is_forbidden(): void
    {
        $group = $this->makeGroup();
        $user = $this->makeUserWithRole('observador', $group);

        $response = $this->actingAs($user)->postJson("/api/groups/{$group->id}/meetings", [
            'meeting_date' => '2026-07-16',
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('reason', 'role');
        $this->assertEquals(0, Meeting::where('group_id', $group->id)->count());
    }

    public function test_miembro_role_is_forbidden(): void
    {
        $group = $this->makeGroup();
        $user = $this->makeUserWithRole('miembro', $group);

        $response = $this->actingAs($user)->postJson("/api/groups/{$group->id}/meetings", [
            'meeting_date' => '2026-07-16',
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('reason', 'role');
        $this->assertEquals(0, Meeting::where('group_id', $group->id)->count());
    }

    public function test_tesorero_of_another_group_is_forbidden(): void
    {
        $groupA = $this->makeGroup();
        $groupB = $this->makeGroup();
        $user = $this->makeUserWithRole('tesorero', $groupA);

        $response = $this->actingAs($user)->postJson("/api/groups/{$groupB->id}/meetings", [
            'meeting_date' => '2026-07-16',
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('reason', 'role');
        $this->assertEquals(0, Meeting::where('group_id', $groupB->id)->count());
    }

    // ---- 409: already-open meeting ----

    public function test_returns_409_with_existing_meeting_when_group_already_has_open_meeting(): void
    {
        $group = $this->makeGroup(['registration_mode' => 'full']);
        $existing = $this->makeMeeting($group, [
            'meeting_number' => 3,
            'meeting_date' => '2026-07-01',
            'month' => 'Julio',
            'status' => 'open',
        ]);
        $user = $this->makeUserWithRole('tesorero', $group);

        $response = $this->actingAs($user)->postJson("/api/groups/{$group->id}/meetings", [
            'meeting_date' => '2026-07-16',
        ]);

        $response->assertStatus(409);
        $response->assertJsonPath('reason', 'meeting_already_open');
        $response->assertJsonPath('meeting.id', $existing->id);
        $response->assertJsonPath('meeting.meeting_number', 3);
        $response->assertJsonPath('meeting.is_partial', false);
        $this->assertEquals(1, Meeting::where('group_id', $group->id)->count());
    }

    // ---- 201: happy path full group ----

    public function test_creates_meeting_for_full_group_with_seeded_attendance_and_contributions(): void
    {
        $group = $this->makeGroup(['registration_mode' => 'full']);
        $this->makeMember($group, ['full_name' => 'Miembro Uno']);
        $this->makeMember($group, ['full_name' => 'Miembro Dos']);
        $user = $this->makeUserWithRole('tesorero', $group);

        $response = $this->actingAs($user)->postJson("/api/groups/{$group->id}/meetings", [
            'meeting_date' => '2026-07-16',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('is_partial', false);
        $response->assertJsonCount(2, 'contributions');
        $response->assertJsonCount(2, 'attendances');
        $response->assertJsonPath('totals', null);

        $meeting = Meeting::where('group_id', $group->id)->first();
        $this->assertNotNull($meeting);
        $this->assertEquals(1, $meeting->meeting_number);
        $this->assertEquals('open', $meeting->status);
        $this->assertEquals(2, Attendance::where('meeting_id', $meeting->id)->count());
        $this->assertEquals(2, MeetingContribution::where('meeting_id', $meeting->id)->count());
    }

    // ---- 201: happy path partial group ----

    public function test_creates_meeting_for_partial_group_with_seeded_total(): void
    {
        $group = $this->makeGroup(['registration_mode' => 'partial']);
        $this->makeMember($group, ['full_name' => 'Miembro Uno']);
        $this->makeMember($group, ['full_name' => 'Miembro Dos']);
        $this->makeMember($group, ['full_name' => 'Miembro Tres']);
        $user = $this->makeUserWithRole('secretario', $group);

        $response = $this->actingAs($user)->postJson("/api/groups/{$group->id}/meetings", [
            'meeting_date' => '2026-07-16',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('is_partial', true);
        $response->assertJsonCount(0, 'contributions');
        $response->assertJsonCount(3, 'attendances');
        $response->assertJsonPath('totals.shares', 0);
        $response->assertJsonPath('totals.emergency_fund', '0.00');
        $response->assertJsonPath('totals.fine', '0.00');

        $meeting = Meeting::where('group_id', $group->id)->first();
        $this->assertEquals(1, MeetingTotal::where('meeting_id', $meeting->id)->count());
        $this->assertEquals(0, MeetingContribution::where('meeting_id', $meeting->id)->count());
    }

    // ---- Validation ----

    public function test_missing_meeting_date_returns_422(): void
    {
        $group = $this->makeGroup();
        $user = $this->makeUserWithRole('tesorero', $group);

        $response = $this->actingAs($user)->postJson("/api/groups/{$group->id}/meetings", []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('meeting_date');
    }

    public function test_invalid_date_format_returns_422(): void
    {
        $group = $this->makeGroup();
        $user = $this->makeUserWithRole('tesorero', $group);

        $response = $this->actingAs($user)->postJson("/api/groups/{$group->id}/meetings", [
            'meeting_date' => 'not-a-date',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('meeting_date');
    }

    // ---- Month derivation ----

    public function test_month_is_derived_server_side_in_spanish_ignoring_client_value(): void
    {
        $group = $this->makeGroup(['registration_mode' => 'full']);
        $user = $this->makeUserWithRole('tesorero', $group);

        $response = $this->actingAs($user)->postJson("/api/groups/{$group->id}/meetings", [
            'meeting_date' => '2026-03-10',
            'month' => 'BogusClientMonth',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('meeting.month', 'Marzo');

        $meeting = Meeting::where('group_id', $group->id)->first();
        $this->assertEquals('Marzo', $meeting->month);
    }

    // ---- Concurrency ----

    public function test_second_sequential_open_attempt_after_first_succeeds_is_rejected(): void
    {
        // sqlite :memory: does not support real concurrent connections/locks
        // the way MySQL does, so true parallel-request concurrency cannot be
        // exercised here. This test instead verifies the SEQUENTIAL
        // consequence of the same lock+check mechanism (design ADR-3): once
        // the first request's transaction commits a new open meeting, an
        // immediately following second request must observe it and be
        // rejected with 409 — no duplicate meeting_number, exactly one open
        // meeting survives.
        $group = $this->makeGroup(['registration_mode' => 'full']);
        $user = $this->makeUserWithRole('tesorero', $group);

        $first = $this->actingAs($user)->postJson("/api/groups/{$group->id}/meetings", [
            'meeting_date' => '2026-07-16',
        ]);
        $second = $this->actingAs($user)->postJson("/api/groups/{$group->id}/meetings", [
            'meeting_date' => '2026-07-17',
        ]);

        $first->assertStatus(201);
        $second->assertStatus(409);
        $second->assertJsonPath('reason', 'meeting_already_open');

        $this->assertEquals(1, Meeting::where('group_id', $group->id)->where('status', 'open')->count());
        $this->assertEquals(1, Meeting::where('group_id', $group->id)->count());
    }

    /**
     * @group skipped-concurrency
     */
    public function test_true_concurrent_requests_do_not_create_duplicate_open_meetings(): void
    {
        $this->markTestSkipped(
            'sqlite :memory: (this suite\'s test driver) cannot exercise true '
            . 'multi-connection row locking the way MySQL/InnoDB does in '
            . 'production — the two connections would not even share the '
            . 'same in-memory database. The DB::transaction + lockForUpdate '
            . 'mechanism (design ADR-3) is exercised sequentially by '
            . 'test_second_sequential_open_attempt_after_first_succeeds_is_rejected() '
            . 'above; genuine concurrent-connection coverage requires a '
            . 'MySQL-backed integration/CI environment (documented, matches '
            . 'the pattern used in the prior mobile-write concurrency change).'
        );
    }

    public function test_meeting_number_does_not_collide_after_rejected_second_attempt(): void
    {
        $group = $this->makeGroup(['registration_mode' => 'full']);
        $this->makeMeeting($group, ['meeting_number' => 7, 'status' => 'closed']);
        $user = $this->makeUserWithRole('tesorero', $group);

        $first = $this->actingAs($user)->postJson("/api/groups/{$group->id}/meetings", [
            'meeting_date' => '2026-07-16',
        ]);
        $second = $this->actingAs($user)->postJson("/api/groups/{$group->id}/meetings", [
            'meeting_date' => '2026-07-17',
        ]);

        $first->assertStatus(201);
        $second->assertStatus(409);

        $this->assertEquals(1, Meeting::where('group_id', $group->id)->where('meeting_number', 8)->count());
        $this->assertEquals(0, Meeting::where('group_id', $group->id)->where('meeting_number', 9)->count());
    }
}
