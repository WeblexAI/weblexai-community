<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class CacheSettings extends Settings
{
    public int $translation_ttl;

    public int $project_config_ttl;

    public int $glossary_ttl;

    public static function group(): string
    {
        return 'cache';
    }

    public function getTranslationTtlInSeconds(): int
    {
        return $this->translation_ttl * 86400;
    }

    public function getProjectConfigTtlInSeconds(): int
    {
        return $this->project_config_ttl * 86400;
    }

    public function getGlossaryTtlInSeconds(): int
    {
        return $this->glossary_ttl * 86400;
    }
}
