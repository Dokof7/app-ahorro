<?php

namespace Tests\Feature\Api;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\Loan;
use App\Models\MeetingContribution;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesGroupFixtures;
use Tests\TestCase;

/**
 * Cross-cutting 403 matrix across all 4 write endpoints: observador,
 * plain miembro, and a privileged role from a different group.
 */
class AuthorizationHardeningTest extends TestCase
{
    use RefreshDatabase;
    use CreatesGroupFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public static function deniedRoleProvider(): array
    {
        return [
            'observador' => ['observador'],
            'miembro' => ['miembro'],
        ];
    }

    /** @dataProvider deniedRoleProvider */
    public function test_contributions_bulk_denies_role(string $role): void
    {
        $group = $this->makeGroup();
        $meeting = $this->makeMeeting($group);
        $member = $this->makeMember($group);
        $contribution = MeetingContribution::create(['meeting_id' => $meeting->id, 'member_id' => $member->id, 'shares' => 0]);
        $user = $this->makeUserWithRole($role, $group);

        $response = $this->actingAs($user)->postJson("/api/meetings/{$meeting->id}/contributions/bulk", [
            'contributions' => [['id' => $contribution->id, 'shares' => 5]],
        ]);

        $response->assertStatus(403);
        $this->assertEquals(0, $contribution->fresh()->shares);
    }

    /** @dataProvider deniedRoleProvider */
    public function test_attendance_bulk_denies_role(string $role): void
    {
        $group = $this->makeGroup();
        $meeting = $this->makeMeeting($group);
        $member = $this->makeMember($group);
        $attendance = Attendance::create(['meeting_id' => $meeting->id, 'member_id' => $member->id, 'status' => 'absent']);
        $user = $this->makeUserWithRole($role, $group);

        $response = $this->actingAs($user)->putJson("/api/meetings/{$meeting->id}/attendance/bulk", [
            'attendances' => [['id' => $attendance->id, 'status' => 'present']],
        ]);

        $response->assertStatus(403);
        $this->assertEquals('absent', $attendance->fresh()->status);
    }

    /** @dataProvider deniedRoleProvider */
    public function test_loan_payment_denies_role(string $role): void
    {
        $group = $this->makeGroup();
        $meeting = $this->makeMeeting($group);
        $member = $this->makeMember($group);
        $loan = Loan::create([
            'member_id' => $member->id,
            'group_id' => $group->id,
            'meeting_id' => $meeting->id,
            'amount' => 100,
            'interest_rate' => 5,
            'delivery_date' => now(),
            'due_date' => now()->addMonth(),
        ]);
        $user = $this->makeUserWithRole($role, $group);

        $response = $this->actingAs($user)->postJson("/api/loans/{$loan->id}/payments", [
            'meeting_id' => $meeting->id,
            'payment_date' => now()->toDateString(),
            'amount_paid' => 10,
            'interest_paid' => 1,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseCount('loan_payments', 0);
    }

    /** @dataProvider deniedRoleProvider */
    public function test_activity_create_denies_role(string $role): void
    {
        $group = $this->makeGroup();
        $user = $this->makeUserWithRole($role, $group);

        $response = $this->actingAs($user)->postJson("/api/groups/{$group->id}/activities", [
            'name' => 'Rifa',
            'activity_date' => now()->toDateString(),
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseCount('activities', 0);
    }

    public function test_cross_group_tesorero_denied_on_contributions(): void
    {
        $group = $this->makeGroup();
        $meeting = $this->makeMeeting($group);
        $member = $this->makeMember($group);
        $contribution = MeetingContribution::create(['meeting_id' => $meeting->id, 'member_id' => $member->id, 'shares' => 0]);

        $otherGroup = $this->makeGroup();
        $user = $this->makeUserWithRole('tesorero', $otherGroup);

        $response = $this->actingAs($user)->postJson("/api/meetings/{$meeting->id}/contributions/bulk", [
            'contributions' => [['id' => $contribution->id, 'shares' => 5]],
        ]);

        $response->assertStatus(403);
        $this->assertEquals(0, $contribution->fresh()->shares);
    }

    public function test_cross_group_tesorero_denied_on_activities(): void
    {
        $group = $this->makeGroup();

        $otherGroup = $this->makeGroup();
        $user = $this->makeUserWithRole('tesorero', $otherGroup);

        $response = $this->actingAs($user)->postJson("/api/groups/{$group->id}/activities", [
            'name' => 'Rifa',
            'activity_date' => now()->toDateString(),
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseCount('activities', 0);
    }
}
