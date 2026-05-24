<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Member;

class MemberPolicy
{
    public function view(User $user, Member $member): bool
    {
        return $user->isAdmin() || $member->group->user_id === $user->id;
    }

    public function update(User $user, Member $member): bool
    {
        return $user->isAdmin() || $member->group->user_id === $user->id;
    }

    public function delete(User $user, Member $member): bool
    {
        return $user->isAdmin() || $member->group->user_id === $user->id;
    }
}
