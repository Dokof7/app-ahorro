<?php

namespace Tests\Feature\Api;

use App\Models\MeetingContribution;
use App\Models\MeetingTotal;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesGroupFixtures;
use Tests\TestCase;

class DashboardApiControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesGroupFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_stats_include_partial_group_totals(): void
    {
        // Partial group: data lives in meeting_totals, not contributions.
        $partial = $this->makeGroup(['registration_mode' => 'partial', 'share_value' => 25]);
        $partialMeeting = $this->makeMeeting($partial);
        MeetingTotal::create([
            'meeting_id' => $partialMeeting->id,
            'shares' => 10, // savings auto-computed: 10 * 25 = 250
            'emergency_fund' => 30,
            'fine' => 5,
        ]);

        // Full group: data lives in meeting_contributions.
        $full = $this->makeGroup(['registration_mode' => 'full', 'share_value' => 25]);
        $fullMeeting = $this->makeMeeting($full);
        $member = $this->makeMember($full);
        MeetingContribution::create([
            'meeting_id' => $fullMeeting->id,
            'member_id' => $member->id,
            'shares' => 4, // savings auto-computed: 4 * 25 = 100
            'emergency_fund' => 20,
            'fine' => 3,
        ]);

        $admin = $this->makeUserWithRole('admin');

        $response = $this->actingAs($admin)->getJson('/api/dashboard');

        $response->assertStatus(200);
        $response->assertJsonPath('stats.total_savings', 350);
        $response->assertJsonPath('stats.total_emergency', 50);
        $response->assertJsonPath('stats.total_fines', 8);
    }

    public function test_chart_includes_partial_group_savings(): void
    {
        $partial = $this->makeGroup(['registration_mode' => 'partial', 'share_value' => 25]);
        $meeting = $this->makeMeeting($partial); // meeting_date = now()
        MeetingTotal::create([
            'meeting_id' => $meeting->id,
            'shares' => 10,
            'emergency_fund' => 30,
            'fine' => 0,
        ]);

        $user = $this->makeUserWithRole('tesorero', $partial);

        $response = $this->actingAs($user)->getJson('/api/dashboard');

        $response->assertStatus(200);
        // Last chart bucket is the current month: must carry the partial totals.
        $response->assertJsonPath('chart.savings.5', 250);
        $response->assertJsonPath('chart.emergency.5', 30);
    }
}
