<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Meeting;

class MeetingPolicy
{
    public function view(User $user, Meeting $meeting): bool
    {
        return $user->isAdmin() || $meeting->group->user_id === $user->id;
    }

    public function update(User $user, Meeting $meeting): bool
    {
        return $user->isAdmin() || $meeting->group->user_id === $user->id;
    }

    public function delete(User $user, Meeting $meeting): bool
    {
        return $user->isAdmin() || $meeting->group->user_id === $user->id;
    }
}
