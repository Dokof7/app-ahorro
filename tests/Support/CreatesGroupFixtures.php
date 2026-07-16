<?php

namespace Tests\Support;

use App\Models\Group;
use App\Models\Meeting;
use App\Models\Member;
use App\Models\User;

/**
 * Shared fixture builders for API feature tests: group/meeting/member/user
 * with role + group-membership wiring, matching production shape (share
 * value, registration mode, open meeting).
 */
trait CreatesGroupFixtures
{
    protected function makeGroup(array $overrides = []): Group
    {
        $owner = User::factory()->create();

        return Group::create(array_merge([
            'user_id' => $owner->id,
            'name' => 'Grupo Test',
            'start_date' => now(),
            'share_value' => 25,
            'registration_mode' => 'full',
        ], $overrides));
    }

    protected function makeMeeting(Group $group, array $overrides = []): Meeting
    {
        return Meeting::create(array_merge([
            'group_id' => $group->id,
            'meeting_number' => 1,
            'meeting_date' => now(),
            'month' => now()->format('F'),
            'status' => 'open',
        ], $overrides));
    }

    protected function makeMember(Group $group, array $overrides = []): Member
    {
        return Member::create(array_merge([
            'group_id' => $group->id,
            'full_name' => 'Miembro Test',
            'join_date' => now(),
            'status' => 'active',
        ], $overrides));
    }

    protected function makeUserWithRole(string $role, ?Group $group = null): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        if ($group) {
            $user->groups()->attach($group->id);
        }

        return $user;
    }
}
