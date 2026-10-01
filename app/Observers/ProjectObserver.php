<?php

namespace App\Observers;

use App\Models\Project;
use App\Services\Cache\ProjectCacheInvalidationService;
use App\Services\ProjectRevisionService;
use Illuminate\Support\Facades\DB;

class ProjectObserver
{
    public function creating(Project $project): void
    {
        $plainTextApiKey = bin2hex(random_bytes(32));
        $project->api_key = $plainTextApiKey;
        $project->api_key_hash = hash('sha256', $plainTextApiKey);
        $project->user_id ??= auth()->id();
    }

    public function created(Project $project): void
    {
        $project->languageSwitcherConfig()->create();
    }

    public function updated(Project $project): void
    {
        $revisions = app(ProjectRevisionService::class);
        if ($project->wasChanged(['provider_credential_id', 'original_language_id', 'website_description', 'translation_tone', 'translation_audience'])) {
            $revisions->bumpGeneration($project->id);
        } else {
            $revisions->bumpDelivery($project->id);
        }
    }

    public function deleted(Project $project): void
    {
        $clear = function () use ($project): void {
            app(ProjectCacheInvalidationService::class)->clearDeletedProject($project->id);
        };

        DB::transactionLevel() > 0 ? DB::afterCommit($clear) : $clear();
    }
}
