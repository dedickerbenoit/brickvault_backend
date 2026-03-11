<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserSet;

class UserSetPolicy
{
    public function view(User $user, UserSet $userSet): bool
    {
        return $user->id === $userSet->user_id;
    }

    public function update(User $user, UserSet $userSet): bool
    {
        return $user->id === $userSet->user_id;
    }

    public function delete(User $user, UserSet $userSet): bool
    {
        return $user->id === $userSet->user_id;
    }
}
