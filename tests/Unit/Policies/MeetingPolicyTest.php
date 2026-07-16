<?php

namespace Tests\Unit\Policies;

use App\Models\Group;
use App\Models\Meeting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function makeGroupWithMeeting(): Meeting
    {
        $owner = User::factory()->create();
        $group = Group::create([
            'user_id' => $owner->id,
            'name' => 'Grupo Test',
            'start_date' => now(),
            'share_value' => 25,
        ]);

        return Meeting::create([
            'group_id' => $group->id,
            'meeting_number' => 1,
            'meeting_date' => now(),
            'month' => now()->format('F'),
            'status' => 'open',
        ]);
    }

    private function userWithRole(string $role, Meeting $meeting): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        $user->groups()->attach($meeting->group_id);

        return $user;
    }

    public function test_tesorero_can_update_meeting_of_own_group(): void
    {
        $meeting = $this->makeGroupWithMeeting();
        $user = $this->userWithRole('tesorero', $meeting);

        $this->assertTrue($user->can('update', $meeting));
    }

    public function test_secretario_can_update_meeting_of_own_group(): void
    {
        $meeting = $this->makeGroupWithMeeting();
        $user = $this->userWithRole('secretario', $meeting);

        $this->assertTrue($user->can('update', $meeting));
    }

    public function test_admin_grupo_can_update_meeting_of_own_group(): void
    {
        $meeting = $this->makeGroupWithMeeting();
        $user = $this->userWithRole('admin_grupo', $meeting);

        $this->assertTrue($user->can('update', $meeting));
    }

    public function test_admin_can_update_any_meeting(): void
    {
        $meeting = $this->makeGroupWithMeeting();
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->assertTrue($user->can('update', $meeting));
    }

    public function test_observador_cannot_update_meeting_of_own_group(): void
    {
        $meeting = $this->makeGroupWithMeeting();
        $user = $this->userWithRole('observador', $meeting);

        $this->assertFalse($user->can('update', $meeting));
    }

    public function test_miembro_cannot_update_meeting_of_own_group(): void
    {
        $meeting = $this->makeGroupWithMeeting();
        $user = $this->userWithRole('miembro', $meeting);

        $this->assertFalse($user->can('update', $meeting));
    }

    public function test_privileged_role_outside_group_cannot_update_meeting(): void
    {
        $meeting = $this->makeGroupWithMeeting();
        $user = User::factory()->create();
        $user->assignRole('tesorero');
        // Not attached to the meeting's group.

        $this->assertFalse($user->can('update', $meeting));
    }
}
