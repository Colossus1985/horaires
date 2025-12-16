<?php

namespace App\Policies;

use App\Models\Overtime;
use App\Models\User;

class OvertimePolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Overtime $overtime): bool
    {
        return $user->id === $overtime->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Overtime $overtime): bool
    {
        return $user->id === $overtime->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Overtime $overtime): bool
    {
        return $user->id === $overtime->user_id;
    }
}
