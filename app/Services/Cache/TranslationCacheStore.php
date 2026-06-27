<?php

namespace App\Services\Cache;

use App\Settings\CacheSettings;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

class TranslationCacheStore
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

    public function get(int $projectId, int $pageId, string $langCode, string $textHash): ?array
    {
        return $this->cache()->get($this->key($projectId, $pageId, $langCode, $textHash));
    }

    public function getMany(int $projectId, int $pageId, string $langCode, array $textHashes): array
    {
        if ($textHashes === []) {
            return [];
        }

        $keys = collect($textHashes)->mapWithKeys(
            fn (string $hash): array => [$hash => $this->key($projectId, $pageId, $langCode, $hash)],
        );
        $values = $this->cache()->many($keys->values()->all());

        return $keys->mapWithKeys(
            fn (string $key, string $hash): array => [$hash => $values[$key] ?? null],
        )->all();
    }

    public function set(
        int $projectId,
        int $pageId,
        string $langCode,
        string $textHash,
        string|array $translated,
    ): void {
        $this->setMany($projectId, $pageId, $langCode, [$textHash => $translated]);
    }

    public function setMany(int $projectId, int $pageId, string $langCode, array $translations): void
    {
        if ($translations === []) {
            return;
        }

        $cache = $this->cache();
        $ttl = app(CacheSettings::class)->getTranslationTtlInSeconds();
        $items = [];

        foreach ($translations as $hash => $payload) {
            $items[$this->key($projectId, $pageId, $langCode, $hash)] = is_string($payload)
                ? ['translated' => $payload, 'translation_id' => null, 'last_used_at' => null]
                : $payload;
        }

        $cache->putMany($items, $ttl);
        $registryKey = $this->registryKey($projectId);
        $cache->put($registryKey, array_values(array_unique([
            ...$cache->get($registryKey, []),
            ...array_keys($items),
        ])), $ttl);
    }
}
