<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Group;

class GroupPolicy
{
    public function view(User $user, Group $group): bool
    {
        return $user->isAdmin() || $group->user_id === $user->id;
    }

    public function update(User $user, Group $group): bool
    {
        return $user->isAdmin() || $group->user_id === $user->id;
    }

    public function delete(User $user, Group $group): bool
    {
        return $user->isAdmin() || $group->user_id === $user->id;
    }
}
