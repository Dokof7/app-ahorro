<?php

namespace Tests\Feature\Api;

use App\Models\Attendance;
use App\Models\Loan;
use App\Models\MeetingContribution;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesGroupFixtures;
use Tests\TestCase;

/**
 * Write to a closed meeting must be rejected on all 3 meeting-scoped
 * endpoints: contributions, attendance, loan payment.
 */
class OpenMeetingGateTest extends TestCase
{
    use RefreshDatabase;
    use CreatesGroupFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_contributions_bulk_rejected_on_closed_meeting(): void
    {
        $group = $this->makeGroup();
        $meeting = $this->makeMeeting($group, ['status' => 'closed']);
        $member = $this->makeMember($group);
        $contribution = MeetingContribution::create(['meeting_id' => $meeting->id, 'member_id' => $member->id, 'shares' => 0]);
        $user = $this->makeUserWithRole('tesorero', $group);

        $response = $this->actingAs($user)->postJson("/api/meetings/{$meeting->id}/contributions/bulk", [
            'contributions' => [['id' => $contribution->id, 'shares' => 5]],
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('reason', 'closed');
        $this->assertEquals(0, $contribution->fresh()->shares);
    }

    public function test_attendance_bulk_rejected_on_closed_meeting(): void
    {
        $group = $this->makeGroup();
        $meeting = $this->makeMeeting($group, ['status' => 'closed']);
        $member = $this->makeMember($group);
        $attendance = Attendance::create(['meeting_id' => $meeting->id, 'member_id' => $member->id, 'status' => 'absent']);
        $user = $this->makeUserWithRole('tesorero', $group);

        $response = $this->actingAs($user)->putJson("/api/meetings/{$meeting->id}/attendance/bulk", [
            'attendances' => [['id' => $attendance->id, 'status' => 'present']],
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('reason', 'closed');
        $this->assertEquals('absent', $attendance->fresh()->status);
    }

    public function test_loan_payment_rejected_when_referenced_meeting_closed(): void
    {
        $group = $this->makeGroup();
        $meeting = $this->makeMeeting($group, ['status' => 'closed']);
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
        $user = $this->makeUserWithRole('tesorero', $group);

        $response = $this->actingAs($user)->postJson("/api/loans/{$loan->id}/payments", [
            'meeting_id' => $meeting->id,
            'payment_date' => now()->toDateString(),
            'amount_paid' => 10,
            'interest_paid' => 1,
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('reason', 'closed');
        $this->assertDatabaseCount('loan_payments', 0);
    }
}
