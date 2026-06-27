<?php

namespace App\Observers;

use App\Enums\UserRole;
use App\Models\User;

class UserObserver
{
    public function created(User $user): void
    {
        $user->assignRole(UserRole::USER->value);
    }
}
