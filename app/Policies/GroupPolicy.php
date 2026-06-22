<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Group;

class GroupPolicy
{
    public function view(User $user, Group $group): bool
    {
        return $user->isAdmin() || $user->groups()->where('groups.id', $group->id)->exists();
    }

    public function update(User $user, Group $group): bool
    {
        return $user->isAdmin() || $user->groups()->where('groups.id', $group->id)->exists();
    }

    public function delete(User $user, Group $group): bool
    {
        return $user->isAdmin();
    }
}
