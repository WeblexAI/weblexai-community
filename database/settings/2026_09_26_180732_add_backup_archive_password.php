<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('backup.archive_password')) {
            $this->migrator->addEncrypted('backup.archive_password', '');
        }
    }
};
