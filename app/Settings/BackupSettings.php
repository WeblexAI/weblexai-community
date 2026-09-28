<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class BackupSettings extends Settings
{
    public string $archive_password;

    public static function group(): string
    {
        return 'backup';
    }

    public static function encrypted(): array
    {
        return [
            'archive_password',
        ];
    }
}
