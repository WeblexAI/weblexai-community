<?php

namespace App\Services\Cache;

use App\Models\Project;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

class ConfigCacheInvalidationService
{
    private function cache(): Repository
    {
        return Cache::store(config('cache.default'));
    }

    private function key(int $projectId, string $pageDomain): string
    {
        return "config:project_{$projectId}:page_".md5($pageDomain);
    }

    public function clearPage(int $projectId, string $pageDomain): void
    {
        $this->cache()->forget($this->key($projectId, $pageDomain));
    }

    public function clearProject(int $projectId): int
    {
        $cache = $this->cache();
        $registryKey = "config:registry:project_{$projectId}";

        foreach ($cache->get($registryKey, []) as $key) {
            $cache->forget($key);
        }

        $cache->forget($registryKey);

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
        $cache = $this->cache();

        foreach ($cache->get('config:registry:all', []) as $projectId) {
            $this->clearProject((int) $projectId);
        }

        $cache->forget('config:registry:all');

        return 1;
    }
}
