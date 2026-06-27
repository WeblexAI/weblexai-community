<?php

namespace App\Services\Cache;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

class TranslationCacheInvalidationService
{
    private function cache(): Repository
    {
        return Cache::store(config('cache.default'));
    }

    private function key(int $projectId, int $pageId, string $langCode, string $textHash): string
    {
        return "translations:{$projectId}:{$pageId}:{$langCode}:{$textHash}";
    }

    private function registryKey(int $projectId): string
    {
        return "translations:registry:project:{$projectId}";
    }

    public function forget(int $projectId, int $pageId, string $langCode, string $textHash): void
    {
        $this->cache()->forget($this->key($projectId, $pageId, $langCode, $textHash));
    }

    public function forgetPageLang(int $projectId, int $pageId, string $langCode): void
    {
        $prefix = "translations:{$projectId}:{$pageId}:{$langCode}:";
        $this->forgetMatching($projectId, fn (string $key): bool => str_starts_with($key, $prefix));
    }

    public function forgetPage(int $projectId, int $pageId): void
    {
        $prefix = "translations:{$projectId}:{$pageId}:";
        $this->forgetMatching($projectId, fn (string $key): bool => str_starts_with($key, $prefix));
    }

    public function forgetProject(int $projectId): void
    {
        $this->forgetMatching($projectId, fn (): bool => true);
    }

    public function forgetMany(
        int $projectId,
        int $pageId,
        string $langCode,
        array $textHashes,
    ): void {
        foreach ($textHashes as $textHash) {
            $this->forget($projectId, $pageId, $langCode, $textHash);
        }
    }

    private function forgetMatching(int $projectId, callable $matches): void
    {
        $cache = $this->cache();
        $registryKey = $this->registryKey($projectId);
        $registry = $cache->get($registryKey, []);
        $forgotten = array_values(array_filter($registry, $matches));

        foreach ($forgotten as $key) {
            $cache->forget($key);
        }

        $remaining = array_values(array_diff($registry, $forgotten));
        $remaining === []
            ? $cache->forget($registryKey)
            : $cache->forever($registryKey, $remaining);
    }
}
