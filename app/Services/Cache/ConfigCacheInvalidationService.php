<?php

namespace App\Services\Cache;

use App\Models\Project;
use App\Services\ProjectRevisionService;

class ConfigCacheInvalidationService
{
    public function __construct(private readonly ProjectRevisionService $revisions) {}

    public function clearPage(int $projectId, string $pageDomain): void
    {
        $this->revisions->bumpDelivery($projectId);
    }

    public function clearProject(int $projectId): int
    {
        $this->revisions->bumpDelivery($projectId);

        return 1;
    }

    public function clearProjects(iterable $projectIds): int
    {
        $projectIds = collect($projectIds)->filter()->unique()->values();

        foreach ($projectIds as $projectId) {
            $this->clearProject((int) $projectId);
        }

        return $projectIds->count();
    }

    public function clearProjectsUsingLanguage(int $languageId): int
    {
        return $this->clearProjects(
            Project::query()
                ->where('original_language_id', $languageId)
                ->orWhereHas('languages', fn ($query) => $query->where('languages.id', $languageId))
                ->pluck('projects.id'),
        );
    }

    public function clearAll(): int
    {
        return $this->clearProjects(Project::query()->pluck('id'));
    }
}
