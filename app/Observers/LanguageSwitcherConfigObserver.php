<?php

namespace App\Observers;

use App\Models\LanguageSwitcherConfig;
use App\Services\Cache\ProjectCacheInvalidationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class LanguageSwitcherConfigObserver implements ShouldHandleEventsAfterCommit
{
    public function updated(LanguageSwitcherConfig $languageSwitcherConfig): void
    {
        app(ProjectCacheInvalidationService::class)->clearProjectConfig($languageSwitcherConfig->project_id);
    }

    public function deleted(LanguageSwitcherConfig $languageSwitcherConfig): void
    {
        app(ProjectCacheInvalidationService::class)->clearProjectConfig($languageSwitcherConfig->project_id);
    }
}
