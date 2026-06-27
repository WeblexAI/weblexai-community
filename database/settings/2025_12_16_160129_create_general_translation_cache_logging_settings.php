<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.marketing_url', 'https://weblexai.com');
        $this->migrator->add('general.dashboard_url', config('app.url'));
        $this->migrator->add('general.app_currency', 'USD');
        $this->migrator->add('general.cdn_url', config('app.url'));

        $this->migrator->add('cache.translation_ttl', 90);
        $this->migrator->add('cache.project_config_ttl', 90);
        $this->migrator->add('cache.glossary_ttl', 90);

        $this->migrator->addEncrypted('maxmind.license_key', '');
        $this->migrator->add('maxmind.user_id', '');
    }
};
