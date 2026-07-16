<?php

namespace Tests\Feature\Api;

use App\Models\MeetingContribution;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesGroupFixtures;
use Tests\TestCase;

class ContributionApiControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesGroupFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_non_partial_group_bulk_contribution_updates_per_member_rows(): void
    {
        $group = $this->makeGroup(['registration_mode' => 'full']);
        $meeting = $this->makeMeeting($group);
        $member1 = $this->makeMember($group, ['full_name' => 'Miembro 1']);
        $member2 = $this->makeMember($group, ['full_name' => 'Miembro 2']);

        $c1 = MeetingContribution::create(['meeting_id' => $meeting->id, 'member_id' => $member1->id, 'shares' => 0]);
        $c2 = MeetingContribution::create(['meeting_id' => $meeting->id, 'member_id' => $member2->id, 'shares' => 0]);

        $user = $this->makeUserWithRole('tesorero', $group);

        $response = $this->actingAs($user)->postJson("/api/meetings/{$meeting->id}/contributions/bulk", [
            'contributions' => [
                ['id' => $c1->id, 'shares' => 4, 'emergency_fund' => 5, 'fine' => 0],
                ['id' => $c2->id, 'shares' => 2, 'emergency_fund' => 0, 'fine' => 1],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['summary', 'totals']);

        $this->assertEquals(100, $c1->fresh()->savings); // 4 shares * 25
        $this->assertEquals(50, $c2->fresh()->savings); // 2 shares * 25
    }

    public function test_partial_group_bulk_contribution_persists_single_total(): void
    {
        $group = $this->makeGroup(['registration_mode' => 'partial']);
        $meeting = $this->makeMeeting($group);
        $user = $this->makeUserWithRole('tesorero', $group);

        $response = $this->actingAs($user)->postJson("/api/meetings/{$meeting->id}/contributions/bulk", [
            'shares' => 10,
            'emergency_fund' => 20,
            'fine' => 5,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('meeting_totals', [
            'meeting_id' => $meeting->id,
            'shares' => 10,
            'emergency_fund' => 20,
            'fine' => 5,
        ]);
    }

    public function test_shares_out_of_range_rejected_with_422(): void
    {
        $group = $this->makeGroup(['registration_mode' => 'full']);
        $meeting = $this->makeMeeting($group);
        $member = $this->makeMember($group);
        $c1 = MeetingContribution::create(['meeting_id' => $meeting->id, 'member_id' => $member->id, 'shares' => 0]);
        $user = $this->makeUserWithRole('tesorero', $group);

        $response = $this->actingAs($user)->postJson("/api/meetings/{$meeting->id}/contributions/bulk", [
            'contributions' => [
                ['id' => $c1->id, 'shares' => 26, 'emergency_fund' => 0, 'fine' => 0],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertEquals(0, $c1->fresh()->shares);
    }

    public function test_partial_group_shares_over_max_rejected_with_422(): void
    {
        $group = $this->makeGroup(['registration_mode' => 'partial']);
        $meeting = $this->makeMeeting($group);
        $user = $this->makeUserWithRole('tesorero', $group);

        $response = $this->actingAs($user)->postJson("/api/meetings/{$meeting->id}/contributions/bulk", [
            'shares' => 26,
            'emergency_fund' => 0,
            'fine' => 0,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('meeting_totals', [
            'meeting_id' => $meeting->id,
            'shares' => 26,
        ]);
    }

    public function test_negative_amount_rejected_with_422(): void
    {
        $group = $this->makeGroup(['registration_mode' => 'partial']);
        $meeting = $this->makeMeeting($group);
        $user = $this->makeUserWithRole('tesorero', $group);

        $response = $this->actingAs($user)->postJson("/api/meetings/{$meeting->id}/contributions/bulk", [
            'shares' => 5,
            'emergency_fund' => -1,
            'fine' => 0,
        ]);

        $response->assertStatus(422);
    }
}
