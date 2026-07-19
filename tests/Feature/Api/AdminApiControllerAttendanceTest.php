<?php

namespace Tests\Feature\Api;

use App\Models\Attendance;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesGroupFixtures;
use Tests\TestCase;

class AdminApiControllerAttendanceTest extends TestCase
{
    use RefreshDatabase;
    use CreatesGroupFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_admin_sees_per_member_attendance_for_a_meeting(): void
    {
        $group = $this->makeGroup();
        $meeting = $this->makeMeeting($group, ['status' => 'closed']);
        $m1 = $this->makeMember($group, ['full_name' => 'Ana Flores']);
        $m2 = $this->makeMember($group, ['full_name' => 'Beto Rojas']);
        Attendance::create(['meeting_id' => $meeting->id, 'member_id' => $m1->id, 'status' => 'present']);
        Attendance::create(['meeting_id' => $meeting->id, 'member_id' => $m2->id, 'status' => 'excused', 'observations' => 'Viaje']);
        $admin = $this->makeUserWithRole('admin');

        $response = $this->actingAs($admin)->getJson("/api/admin/meetings/{$meeting->id}/attendance");

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.full_name', 'Ana Flores');
        $response->assertJsonPath('data.0.status', 'present');
        $response->assertJsonPath('data.1.full_name', 'Beto Rojas');
        $response->assertJsonPath('data.1.status', 'excused');
        $response->assertJsonPath('data.1.observations', 'Viaje');
    }

    public function test_non_admin_roles_are_forbidden(): void
    {
        $group = $this->makeGroup();
        $meeting = $this->makeMeeting($group);
        $user = $this->makeUserWithRole('tesorero', $group);

        $this->actingAs($user)
            ->getJson("/api/admin/meetings/{$meeting->id}/attendance")
            ->assertStatus(403);
    }

    public function test_admin_grupo_cannot_see_other_groups_attendance(): void
    {
        $groupA = $this->makeGroup();
        $groupB = $this->makeGroup();
        $meeting = $this->makeMeeting($groupB);
        $user = $this->makeUserWithRole('admin_grupo', $groupA);

        $this->actingAs($user)
            ->getJson("/api/admin/meetings/{$meeting->id}/attendance")
            ->assertStatus(403);
    }
}
