<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Loan;

class LoanPolicy
{
    public function view(User $user, Loan $loan): bool
    {
        return $user->isAdmin() || $user->groups()->where('groups.id', $loan->group_id)->exists();
    }

    public function delete(User $user, Loan $loan): bool
    {
        return $user->isAdmin();
    }
}
