<?php

namespace App\Services\Cache;

use App\Settings\CacheSettings;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

class ConfigCacheStore
{
    private function cache(): Repository
    {
        return Cache::store(config('cache.default'));
    }

    private function key(int $projectId, string $pageDomain): string
    {
        return "config:project_{$projectId}:page_".md5($pageDomain);
    }

    public function get(int $projectId, string $pageDomain): ?array
    {
        return $this->cache()->get($this->key($projectId, $pageDomain));
    }

    public function set(int $projectId, string $pageDomain, array $config): void
    {
        $cache = $this->cache();
        $ttl = app(CacheSettings::class)->getProjectConfigTtlInSeconds();
        $key = $this->key($projectId, $pageDomain);
        $registryKey = "config:registry:project_{$projectId}";

        $cache->put($key, $config, $ttl);
        $cache->put($registryKey, array_values(array_unique([
            ...$cache->get($registryKey, []),
            $key,
        ])), $ttl);
        $cache->put('config:registry:all', array_values(array_unique([
            ...$cache->get('config:registry:all', []),
            $projectId,
        ])), $ttl);
    }

    public function has(int $projectId, string $pageDomain): bool
    {
        return $this->cache()->has($this->key($projectId, $pageDomain));
    }
}
