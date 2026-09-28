<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('error_reporting.enabled')) {
            $this->migrator->add('error_reporting.enabled', false);
        }
    }
};
