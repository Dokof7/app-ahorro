<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Meeting;

class MeetingPolicy
{
    public function view(User $user, Meeting $meeting): bool
    {
        return $user->isAdmin() || $user->groups()->where('groups.id', $meeting->group_id)->exists();
    }

    public function update(User $user, Meeting $meeting): bool
    {
        return $user->isAdmin()
            || ($user->canEdit() && $user->groups()->where('groups.id', $meeting->group_id)->exists());
    }

    public function delete(User $user, Meeting $meeting): bool
    {
        return $user->isAdmin();
    }
}
