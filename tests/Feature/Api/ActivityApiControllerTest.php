<?php

namespace Tests\Feature\Api;

use App\Models\Activity;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesGroupFixtures;
use Tests\TestCase;

class ActivityApiControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesGroupFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_authorized_user_can_create_activity(): void
    {
        $group = $this->makeGroup();
        $user = $this->makeUserWithRole('secretario', $group);

        $response = $this->actingAs($user)->postJson("/api/groups/{$group->id}/activities", [
            'name' => 'Rifa',
            'activity_date' => now()->toDateString(),
            'location' => 'Salón comunal',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('activities', ['group_id' => $group->id, 'name' => 'Rifa']);
    }

    public function test_missing_required_field_rejected_with_422(): void
    {
        $group = $this->makeGroup();
        $user = $this->makeUserWithRole('secretario', $group);

        $response = $this->actingAs($user)->postJson("/api/groups/{$group->id}/activities", [
            'location' => 'Salón comunal',
        ]);

        $response->assertStatus(422);
    }

    public function test_list_activities_for_group(): void
    {
        $group = $this->makeGroup();
        $user = $this->makeUserWithRole('secretario', $group);
        Activity::create([
            'group_id' => $group->id,
            'name' => 'Rifa',
            'activity_date' => now(),
        ]);

        $response = $this->actingAs($user)->getJson("/api/groups/{$group->id}/activities");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_update_and_delete_follow_authorization_gate(): void
    {
        $group = $this->makeGroup();
        $user = $this->makeUserWithRole('secretario', $group);
        $activity = Activity::create([
            'group_id' => $group->id,
            'name' => 'Rifa',
            'activity_date' => now(),
        ]);

        $updateResponse = $this->actingAs($user)->putJson("/api/activities/{$activity->id}", [
            'name' => 'Rifa actualizada',
            'activity_date' => now()->toDateString(),
        ]);
        $updateResponse->assertStatus(200);
        $this->assertEquals('Rifa actualizada', $activity->fresh()->name);

        $deleteResponse = $this->actingAs($user)->deleteJson("/api/activities/{$activity->id}");
        $deleteResponse->assertStatus(200);
        $this->assertDatabaseMissing('activities', ['id' => $activity->id]);
    }

    public function test_cross_group_delete_rejected(): void
    {
        $group = $this->makeGroup();
        $activity = Activity::create([
            'group_id' => $group->id,
            'name' => 'Rifa',
            'activity_date' => now(),
        ]);

        $otherGroup = $this->makeGroup();
        $user = $this->makeUserWithRole('secretario', $otherGroup);

        $response = $this->actingAs($user)->deleteJson("/api/activities/{$activity->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('activities', ['id' => $activity->id]);
    }

    public function test_observador_cannot_create_activity(): void
    {
        $group = $this->makeGroup();
        $user = $this->makeUserWithRole('observador', $group);

        $response = $this->actingAs($user)->postJson("/api/groups/{$group->id}/activities", [
            'name' => 'Rifa',
            'activity_date' => now()->toDateString(),
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseCount('activities', 0);
    }
}
