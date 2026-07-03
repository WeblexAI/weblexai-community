<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->addMissing('cache.translation_ttl', 90);
        $this->addMissing('cache.project_config_ttl', 90);
        $this->addMissing('cache.glossary_ttl', 90);

        $this->addMissingEncrypted('maxmind.license_key', '');
        $this->addMissing('maxmind.user_id', '');
    }

    private function addMissing(string $property, mixed $payload): void
    {
        if (! $this->migrator->exists($property)) {
            $this->migrator->add($property, $payload);
        }
    }

    private function addMissingEncrypted(string $property, mixed $payload): void
    {
        if (! $this->migrator->exists($property)) {
            $this->migrator->addEncrypted($property, $payload);
        }
    }
};
