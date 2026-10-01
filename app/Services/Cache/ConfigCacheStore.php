<?php

namespace App\Services\Cache;

use App\Models\Project;
use App\Settings\CacheSettings;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

class ConfigCacheStore
{
    private function cache(): Repository
    {
        return Cache::store(config('cache.default'));
    }

    private function key(int $projectId, string $pageDomain, ?int $revision = null): string
    {
        $revision ??= (int) Project::query()->whereKey($projectId)->value('delivery_revision');

        return "config:v{$revision}:project_{$projectId}:page_".md5($pageDomain);
    }

    public function get(int $projectId, string $pageDomain, ?int $revision = null): ?array
    {
        try {
            return $this->cache()->get($this->key($projectId, $pageDomain, $revision));
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }

    public function set(int $projectId, string $pageDomain, array $config, ?int $revision = null): void
    {
        try {
            $this->cache()->put(
                $this->key($projectId, $pageDomain, $revision),
                $config,
                app(CacheSettings::class)->getProjectConfigTtlInSeconds(),
            );
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function has(int $projectId, string $pageDomain): bool
    {
        return $this->cache()->has($this->key($projectId, $pageDomain));
    }
}
