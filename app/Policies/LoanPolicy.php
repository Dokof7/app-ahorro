<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Loan;

class LoanPolicy
{
    public function view(User $user, Loan $loan): bool
    {
        return $user->isAdmin() || $loan->group->user_id === $user->id;
    }

    public function delete(User $user, Loan $loan): bool
    {
        return $user->isAdmin() || $loan->group->user_id === $user->id;
    }
}
