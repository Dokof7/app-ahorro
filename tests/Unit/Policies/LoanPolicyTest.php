<?php

namespace Tests\Unit\Policies;

use App\Models\Group;
use App\Models\Loan;
use App\Models\Meeting;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function makeLoan(): Loan
    {
        $owner = User::factory()->create();
        $group = Group::create([
            'user_id' => $owner->id,
            'name' => 'Grupo Test',
            'start_date' => now(),
            'share_value' => 25,
        ]);

        $meeting = Meeting::create([
            'group_id' => $group->id,
            'meeting_number' => 1,
            'meeting_date' => now(),
            'month' => now()->format('F'),
            'status' => 'open',
        ]);

        $member = Member::create([
            'group_id' => $group->id,
            'full_name' => 'Miembro Test',
            'join_date' => now(),
            'status' => 'active',
        ]);

        return Loan::create([
            'member_id' => $member->id,
            'group_id' => $group->id,
            'meeting_id' => $meeting->id,
            'amount' => 100,
            'interest_rate' => 5,
            'delivery_date' => now(),
            'due_date' => now()->addMonth(),
        ]);
    }

    private function userWithRole(string $role, Loan $loan): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        $user->groups()->attach($loan->group_id);

        return $user;
    }

    public function test_tesorero_can_create_payment_for_own_group_loan(): void
    {
        $loan = $this->makeLoan();
        $user = $this->userWithRole('tesorero', $loan);

        $this->assertTrue($user->can('createPayment', $loan));
    }

    public function test_secretario_can_create_payment_for_own_group_loan(): void
    {
        $loan = $this->makeLoan();
        $user = $this->userWithRole('secretario', $loan);

        $this->assertTrue($user->can('createPayment', $loan));
    }

    public function test_admin_grupo_can_create_payment_for_own_group_loan(): void
    {
        $loan = $this->makeLoan();
        $user = $this->userWithRole('admin_grupo', $loan);

        $this->assertTrue($user->can('createPayment', $loan));
    }

    public function test_admin_can_create_payment_for_any_loan(): void
    {
        $loan = $this->makeLoan();
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->assertTrue($user->can('createPayment', $loan));
    }

    public function test_observador_cannot_create_payment(): void
    {
        $loan = $this->makeLoan();
        $user = $this->userWithRole('observador', $loan);

        $this->assertFalse($user->can('createPayment', $loan));
    }

    public function test_miembro_cannot_create_payment(): void
    {
        $loan = $this->makeLoan();
        $user = $this->userWithRole('miembro', $loan);

        $this->assertFalse($user->can('createPayment', $loan));
    }

    public function test_privileged_role_outside_group_cannot_create_payment(): void
    {
        $loan = $this->makeLoan();
        $user = User::factory()->create();
        $user->assignRole('tesorero');
        // Not attached to the loan's group.

        $this->assertFalse($user->can('createPayment', $loan));
    }
}
