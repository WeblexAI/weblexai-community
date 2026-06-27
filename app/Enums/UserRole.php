<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case USER = 'user';

    public static function toArray(): array
    {
        return [
            self::ADMIN->value,
            self::USER->value,
        ];
    }
}
