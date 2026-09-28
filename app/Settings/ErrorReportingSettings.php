<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ErrorReportingSettings extends Settings
{
    public bool $enabled;

    public static function group(): string
    {
        return 'error_reporting';
    }
}
