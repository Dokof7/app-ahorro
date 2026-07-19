<?php

namespace Tests\Feature\Api;

use App\Models\MeetingContribution;
use App\Models\MeetingTotal;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesGroupFixtures;
use Tests\TestCase;

class AdminApiControllerMeetingsTest extends TestCase
{
    use RefreshDatabase;
    use CreatesGroupFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_partial_group_meetings_carry_amounts_from_meeting_totals(): void
    {
        $group = $this->makeGroup(['registration_mode' => 'partial', 'share_value' => 10]);
        $meeting = $this->makeMeeting($group, ['status' => 'closed']);
        MeetingTotal::create([
            'meeting_id' => $meeting->id,
            'shares' => 12, // savings auto-computed: 12 * 10 = 120
            'emergency_fund' => 45,
            'fine' => 7,
        ]);
        $admin = $this->makeUserWithRole('admin');

        $response = $this->actingAs($admin)->getJson("/api/admin/groups/{$group->id}/meetings");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $meeting->id);
        $response->assertJsonPath('data.0.meeting_number', 1);
        $response->assertJsonPath('data.0.status', 'closed');
        $response->assertJsonPath('data.0.savings', 120);
        $response->assertJsonPath('data.0.emergency', 45);
        $response->assertJsonPath('data.0.fines', 7);
    }

    public function test_full_group_meetings_carry_amounts_from_contributions(): void
    {
        $group = $this->makeGroup(['registration_mode' => 'full', 'share_value' => 10]);
        $meeting = $this->makeMeeting($group);
        $member = $this->makeMember($group);
        MeetingContribution::create([
            'meeting_id' => $meeting->id,
            'member_id' => $member->id,
            'shares' => 5, // savings auto-computed: 5 * 10 = 50
            'emergency_fund' => 20,
            'fine' => 2,
        ]);
        $admin = $this->makeUserWithRole('admin');

        $response = $this->actingAs($admin)->getJson("/api/admin/groups/{$group->id}/meetings");

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.savings', 50);
        $response->assertJsonPath('data.0.emergency', 20);
        $response->assertJsonPath('data.0.fines', 2);
    }

    public function test_meetings_include_attendance_counts(): void
    {
        $group = $this->makeGroup(['registration_mode' => 'partial']);
        $meeting = $this->makeMeeting($group);
        $m1 = $this->makeMember($group);
        $m2 = $this->makeMember($group, ['full_name' => 'Miembro Dos']);
        \App\Models\Attendance::create(['meeting_id' => $meeting->id, 'member_id' => $m1->id, 'status' => 'present']);
        \App\Models\Attendance::create(['meeting_id' => $meeting->id, 'member_id' => $m2->id, 'status' => 'absent']);
        $admin = $this->makeUserWithRole('admin');

        $response = $this->actingAs($admin)->getJson("/api/admin/groups/{$group->id}/meetings");

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.attended', 1);
        $response->assertJsonPath('data.0.total_attendance', 2);
    }

    public function test_non_admin_roles_are_forbidden(): void
    {
        $group = $this->makeGroup();
        $this->makeMeeting($group);
        $user = $this->makeUserWithRole('tesorero', $group);

        $this->actingAs($user)
            ->getJson("/api/admin/groups/{$group->id}/meetings")
            ->assertStatus(403);
    }

    public function test_admin_grupo_cannot_see_other_groups_meetings(): void
    {
        $groupA = $this->makeGroup();
        $groupB = $this->makeGroup();
        $this->makeMeeting($groupB);
        $user = $this->makeUserWithRole('admin_grupo', $groupA);

        $this->actingAs($user)
            ->getJson("/api/admin/groups/{$groupB->id}/meetings")
            ->assertStatus(403);
    }
}
