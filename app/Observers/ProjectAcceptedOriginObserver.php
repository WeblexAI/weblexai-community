<?php

namespace App\Observers;

use App\Models\ProjectAcceptedOrigin;
use App\Services\Cache\ProjectCacheInvalidationService;
use App\Support\OriginNormalizer;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\DB;

class ProjectAcceptedOriginObserver implements ShouldHandleEventsAfterCommit
{
    public function saving(ProjectAcceptedOrigin $origin): void
    {
        $normalized = app(OriginNormalizer::class)->normalize($origin->origin);
        $origin->origin = $normalized;
        $origin->normalized_origin = $normalized;
    }

    public function saved(ProjectAcceptedOrigin $origin): void
    {
        $this->clearCache($origin);
    }

    public function deleted(ProjectAcceptedOrigin $origin): void
    {
        $this->clearCache($origin);
    }

    private function clearCache(ProjectAcceptedOrigin $origin): void
    {
        $callback = fn () => app(ProjectCacheInvalidationService::class)
            ->clearProjectConfig($origin->project_id);

        DB::transactionLevel() > 0 ? DB::afterCommit($callback) : $callback();
    }
}
