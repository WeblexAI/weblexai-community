<?php

namespace App\Services;

use App\Models\User;

class ProfileService
{
    public static function updateProfile(array $data): User
    {
        $user = auth()->user();
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        return $user;
    }
}
