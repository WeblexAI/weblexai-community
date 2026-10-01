<?php

namespace App\Observers;

use App\Models\Language;
use App\Services\Cache\ConfigCacheInvalidationService;

class LanguageObserver
{
    public function updated(Language $language): void
    {
        app(ConfigCacheInvalidationService::class)->clearProjectsUsingLanguage($language->id);
    }

    public function deleted(Language $language): void
    {
        app(ConfigCacheInvalidationService::class)->clearProjectsUsingLanguage($language->id);
    }
}
