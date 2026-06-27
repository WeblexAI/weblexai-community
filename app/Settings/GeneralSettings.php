<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $marketing_url;

    public string $dashboard_url;

    public string $app_currency;

    public string $cdn_url;

    public static function group(): string
    {
        return 'general';
    }
}
