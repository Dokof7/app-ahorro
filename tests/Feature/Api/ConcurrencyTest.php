<?php

namespace Tests\Feature\Api;

use App\Models\MeetingContribution;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesGroupFixtures;
use Tests\TestCase;

/**
 * Concurrency-Safe Summary Recalculation (spec: meeting-summary-recalc).
 *
 * True multi-connection concurrent locking (two DB connections racing on
 * Meeting::lockForUpdate()) cannot be reliably simulated in this test suite:
 * phpunit.xml runs against a single sqlite `:memory:` connection shared by
 * the whole process, and sqlite's `:memory:` databases are connection-scoped
 * (a second connection would see an empty, unrelated database) while
 * `lockForUpdate()` itself is a no-op hint on sqlite (no real row-level
 * locking engine, unlike MySQL/Postgres). Spinning up a second real
 * connection to the SAME in-memory DB, or forking a process, would be
 * fragile and environment-specific rather than a trustworthy assertion.
 *
 * What IS verified here, against the real code path used by
 * ContributionApiController::bulkStore, is the outcome the lock exists to
 * protect: two sequential writes to the same open meeting each complete
 * their own transaction and BOTH persist correctly with no lost update —
 * i.e. the second write does not clobber/ignore the first write's summary
 * contribution. This exercises the same `DB::transaction` + `lockForUpdate`
 * + `recalculateSummary` code path Task 3.4 asks to validate; it does not
 * prove lock-based serialization under true concurrency, which would
 * require a MySQL-backed integration environment (out of scope here).
 */
class ConcurrencyTest extends TestCase
{
    use RefreshDatabase;
    use CreatesGroupFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_two_sequential_writes_to_same_open_meeting_both_persist_no_lost_update(): void
    {
        $group = $this->makeGroup();
        $meeting = $this->makeMeeting($group);
        $member1 = $this->makeMember($group, ['full_name' => 'Miembro 1']);
        $member2 = $this->makeMember($group, ['full_name' => 'Miembro 2']);

        $c1 = MeetingContribution::create(['meeting_id' => $meeting->id, 'member_id' => $member1->id, 'shares' => 0]);
        $c2 = MeetingContribution::create(['meeting_id' => $meeting->id, 'member_id' => $member2->id, 'shares' => 0]);

        $userA = $this->makeUserWithRole('tesorero', $group);
        $userB = $this->makeUserWithRole('secretario', $group);

        // "Writer A" (e.g. device 1) submits member 1's contribution.
        $responseA = $this->actingAs($userA)->postJson("/api/meetings/{$meeting->id}/contributions/bulk", [
            'contributions' => [
                ['id' => $c1->id, 'shares' => 4, 'emergency_fund' => 0, 'fine' => 0],
            ],
        ]);

        // "Writer B" (e.g. device 2) submits member 2's contribution right after.
        $responseB = $this->actingAs($userB)->postJson("/api/meetings/{$meeting->id}/contributions/bulk", [
            'contributions' => [
                ['id' => $c2->id, 'shares' => 2, 'emergency_fund' => 0, 'fine' => 0],
            ],
        ]);

        $responseA->assertStatus(200);
        $responseB->assertStatus(200);

        // Both writes must be reflected — no lost update from B overwriting A's summary contribution.
        $this->assertEquals(100, $c1->fresh()->savings); // 4 shares * 25
        $this->assertEquals(50, $c2->fresh()->savings); // 2 shares * 25

        $summary = $meeting->fresh()->load('summary')->summary;
        $this->assertEquals(150, $summary->income_savings); // 100 + 50, both writes counted
    }

    /**
     * @group skipped-environment-limitation
     */
    public function test_true_concurrent_lock_serialization_requires_multi_connection_backend(): void
    {
        $this->markTestSkipped(
            'True concurrent lockForUpdate() serialization cannot be verified against a single-connection '
            . 'sqlite :memory: test database: lockForUpdate() is a MySQL/Postgres row-lock hint with no effect '
            . 'on sqlite, and a second real connection to the same :memory: database is not visible across '
            . 'connections. Verifying actual lock-based serialization would require a MySQL-backed test '
            . 'environment with two real concurrent connections, which is out of scope for this suite. '
            . 'See test_two_sequential_writes_to_same_open_meeting_both_persist_no_lost_update for the '
            . 'closest in-process verification of the outcome the lock protects.'
        );
    }
}
