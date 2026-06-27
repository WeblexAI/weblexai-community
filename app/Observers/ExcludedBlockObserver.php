<?php

namespace App\Observers;

use App\Models\ExcludedBlock;
use App\Services\Cache\ProjectCacheInvalidationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class ExcludedBlockObserver implements ShouldHandleEventsAfterCommit
{
    public function created(ExcludedBlock $excludedBlock): void
    {
        app(ProjectCacheInvalidationService::class)->clearProjectConfig($excludedBlock->project_id);
    }

    public function updated(ExcludedBlock $excludedBlock): void
    {
        app(ProjectCacheInvalidationService::class)->clearProjectConfig($excludedBlock->project_id);
    }

    public function deleted(ExcludedBlock $excludedBlock): void
    {
        app(ProjectCacheInvalidationService::class)->clearProjectConfig($excludedBlock->project_id);
    }

    public function restored(ExcludedBlock $excludedBlock): void
    {
        app(ProjectCacheInvalidationService::class)->clearProjectConfig($excludedBlock->project_id);
    }

    public function forceDeleted(ExcludedBlock $excludedBlock): void
    {
        app(ProjectCacheInvalidationService::class)->clearProjectConfig($excludedBlock->project_id);
    }
}
