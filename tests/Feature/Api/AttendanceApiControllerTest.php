<?php

namespace Tests\Feature\Api;

use App\Models\Attendance;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesGroupFixtures;
use Tests\TestCase;

class AttendanceApiControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesGroupFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_valid_bulk_attendance_update_persists_status_and_observations(): void
    {
        $group = $this->makeGroup();
        $meeting = $this->makeMeeting($group);
        $member1 = $this->makeMember($group, ['full_name' => 'Miembro 1']);
        $member2 = $this->makeMember($group, ['full_name' => 'Miembro 2']);

        $a1 = Attendance::create(['meeting_id' => $meeting->id, 'member_id' => $member1->id, 'status' => 'absent']);
        $a2 = Attendance::create(['meeting_id' => $meeting->id, 'member_id' => $member2->id, 'status' => 'absent']);

        $user = $this->makeUserWithRole('secretario', $group);

        $response = $this->actingAs($user)->putJson("/api/meetings/{$meeting->id}/attendance/bulk", [
            'attendances' => [
                ['id' => $a1->id, 'status' => 'present'],
                ['id' => $a2->id, 'status' => 'excused', 'observations' => 'Permiso médico'],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertEquals('present', $a1->fresh()->status);
        $this->assertEquals('excused', $a2->fresh()->status);
        $this->assertEquals('Permiso médico', $a2->fresh()->observations);
    }

    public function test_invalid_status_rejected_with_422(): void
    {
        $group = $this->makeGroup();
        $meeting = $this->makeMeeting($group);
        $member = $this->makeMember($group);
        $a1 = Attendance::create(['meeting_id' => $meeting->id, 'member_id' => $member->id, 'status' => 'absent']);
        $user = $this->makeUserWithRole('secretario', $group);

        $response = $this->actingAs($user)->putJson("/api/meetings/{$meeting->id}/attendance/bulk", [
            'attendances' => [
                ['id' => $a1->id, 'status' => 'invalid'],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertEquals('absent', $a1->fresh()->status);
    }

    public function test_member_outside_meeting_group_rejected(): void
    {
        $group = $this->makeGroup();
        $meeting = $this->makeMeeting($group);
        $member = $this->makeMember($group);
        $a1 = Attendance::create(['meeting_id' => $meeting->id, 'member_id' => $member->id, 'status' => 'absent']);

        $otherGroup = $this->makeGroup();
        $otherMeeting = $this->makeMeeting($otherGroup);
        $otherMember = $this->makeMember($otherGroup);
        $otherAttendance = Attendance::create(['meeting_id' => $otherMeeting->id, 'member_id' => $otherMember->id, 'status' => 'absent']);

        $user = $this->makeUserWithRole('secretario', $group);

        // Attempt to update an attendance id that does not belong to $meeting.
        $response = $this->actingAs($user)->putJson("/api/meetings/{$meeting->id}/attendance/bulk", [
            'attendances' => [
                ['id' => $otherAttendance->id, 'status' => 'present'],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertEquals('absent', $otherAttendance->fresh()->status);
    }
}
