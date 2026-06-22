<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Member;

class MemberPolicy
{
    public function view(User $user, Member $member): bool
    {
        return $user->isAdmin() || $user->groups()->where('groups.id', $member->group_id)->exists();
    }

    public function update(User $user, Member $member): bool
    {
        return $user->isAdmin() || $user->groups()->where('groups.id', $member->group_id)->exists();
    }

    public function delete(User $user, Member $member): bool
    {
        return $user->isAdmin();
    }
}
