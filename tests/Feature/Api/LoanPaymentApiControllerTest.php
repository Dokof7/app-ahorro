<?php

namespace Tests\Feature\Api;

use App\Models\Loan;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesGroupFixtures;
use Tests\TestCase;

class LoanPaymentApiControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesGroupFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function makeLoan($group, $meeting, array $overrides = []): Loan
    {
        $member = $this->makeMember($group);

        return Loan::create(array_merge([
            'member_id' => $member->id,
            'group_id' => $group->id,
            'meeting_id' => $meeting->id,
            'amount' => 100,
            'interest_rate' => 10,
            'delivery_date' => now(),
            'due_date' => now()->addMonth(),
        ], $overrides));
    }

    public function test_unauthorized_role_gets_403_and_creates_no_payment(): void
    {
        $group = $this->makeGroup();
        $meeting = $this->makeMeeting($group);
        $loan = $this->makeLoan($group, $meeting);
        $user = $this->makeUserWithRole('observador', $group);

        $response = $this->actingAs($user)->postJson("/api/loans/{$loan->id}/payments", [
            'meeting_id' => $meeting->id,
            'payment_date' => now()->toDateString(),
            'amount_paid' => 10,
            'interest_paid' => 1,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseCount('loan_payments', 0);
    }

    public function test_valid_payment_reduces_remaining_balance(): void
    {
        $group = $this->makeGroup();
        $meeting = $this->makeMeeting($group);
        $loan = $this->makeLoan($group, $meeting);
        $user = $this->makeUserWithRole('tesorero', $group);

        $balanceBefore = $loan->fresh()->balance;

        $response = $this->actingAs($user)->postJson("/api/loans/{$loan->id}/payments", [
            'meeting_id' => $meeting->id,
            'payment_date' => now()->toDateString(),
            'amount_paid' => 20,
            'interest_paid' => 5,
        ]);

        $response->assertStatus(201);
        // LoanPayment::recalculateLoan (existing model behavior, unchanged by
        // this API) sums amount_paid + interest_paid into loan.amount_paid,
        // so balance decreases by both, not just amount_paid.
        $this->assertEquals($balanceBefore - 20 - 5, $loan->fresh()->balance);
        $this->assertDatabaseCount('loan_payments', 1);
    }

    public function test_negative_amount_paid_rejected_with_422(): void
    {
        $group = $this->makeGroup();
        $meeting = $this->makeMeeting($group);
        $loan = $this->makeLoan($group, $meeting);
        $user = $this->makeUserWithRole('tesorero', $group);

        $response = $this->actingAs($user)->postJson("/api/loans/{$loan->id}/payments", [
            'meeting_id' => $meeting->id,
            'payment_date' => now()->toDateString(),
            'amount_paid' => -5,
            'interest_paid' => 1,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('loan_payments', 0);
    }

    public function test_payment_against_closed_meeting_rejected_with_403(): void
    {
        $group = $this->makeGroup();
        $meeting = $this->makeMeeting($group, ['status' => 'closed']);
        $loan = $this->makeLoan($group, $meeting);
        $user = $this->makeUserWithRole('tesorero', $group);

        $response = $this->actingAs($user)->postJson("/api/loans/{$loan->id}/payments", [
            'meeting_id' => $meeting->id,
            'payment_date' => now()->toDateString(),
            'amount_paid' => 10,
            'interest_paid' => 1,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseCount('loan_payments', 0);
    }

    public function test_user_from_another_group_cannot_pay_loan(): void
    {
        $group = $this->makeGroup();
        $meeting = $this->makeMeeting($group);
        $loan = $this->makeLoan($group, $meeting);

        $otherGroup = $this->makeGroup();
        $user = $this->makeUserWithRole('tesorero', $otherGroup);

        $response = $this->actingAs($user)->postJson("/api/loans/{$loan->id}/payments", [
            'meeting_id' => $meeting->id,
            'payment_date' => now()->toDateString(),
            'amount_paid' => 10,
            'interest_paid' => 1,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseCount('loan_payments', 0);
    }
}
