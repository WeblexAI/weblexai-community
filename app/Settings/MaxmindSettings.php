<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class MaxmindSettings extends Settings
{
    public string $license_key;

    public string $user_id;

    public static function group(): string
    {
        return 'maxmind';
    }

    public static function encrypted(): array
    {
        return [
            'license_key',
        ];
    }
}
