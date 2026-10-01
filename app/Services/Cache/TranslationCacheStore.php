<?php

namespace App\Services\Cache;

use App\Models\Project;
use App\Settings\CacheSettings;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

class TranslationCacheStore
{
    private function cache(): Repository
    {
        return Cache::store(config('cache.default'));
    }

    private function key(int $projectId, int $pageId, string $langCode, string $textHash, string $type = 'text', string $attr = '', string $contextHash = '', ?int $revision = null): string
    {
        $revision ??= (int) Project::query()->whereKey($projectId)->value('delivery_revision');

        return "translations:v{$revision}:{$projectId}:{$pageId}:{$langCode}:{$type}:".md5($attr).":{$contextHash}:{$textHash}";
    }

    public function get(int $projectId, int $pageId, string $langCode, string $textHash, string $type = 'text', string $attr = '', string $contextHash = '', ?int $revision = null): ?array
    {
        try {
            return $this->cache()->get($this->key($projectId, $pageId, $langCode, $textHash, $type, $attr, $contextHash, $revision));
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }

    public function getMany(int $projectId, int $pageId, string $langCode, array $identities, ?int $revision = null): array
    {
        if ($identities === []) {
            return [];
        }

        $keys = collect($identities)->mapWithKeys(function (mixed $identity, mixed $key) use ($projectId, $pageId, $langCode, $revision): array {
            $identity = is_array($identity) ? $identity : ['text_hash' => $identity];
            $cacheIdentity = implode('|', [$identity['type'] ?? 'text', $identity['attr'] ?? '', $identity['context_hash'] ?? '', $identity['text_hash']]);

            return [$cacheIdentity => $this->key($projectId, $pageId, $langCode, $identity['text_hash'], $identity['type'] ?? 'text', $identity['attr'] ?? '', $identity['context_hash'] ?? '', $revision)];
        });
        try {
            $values = $this->cache()->many($keys->values()->all());
        } catch (\Throwable $exception) {
            report($exception);
            $values = [];
        }

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
        string $type = 'text',
        string $attr = '',
        string $contextHash = '',
        ?int $revision = null,
    ): void {
        $this->setMany($projectId, $pageId, $langCode, [[
            'text_hash' => $textHash,
            'type' => $type,
            'attr' => $attr,
            'context_hash' => $contextHash,
            'payload' => $translated,
        ]], $revision);
    }

    public function setMany(int $projectId, int $pageId, string $langCode, array $translations, ?int $revision = null): void
    {
        if ($translations === []) {
            return;
        }

        $cache = $this->cache();
        $ttl = app(CacheSettings::class)->getTranslationTtlInSeconds();
        $items = [];

        foreach ($translations as $hash => $payload) {
            $entry = is_array($payload) && array_key_exists('payload', $payload) ? $payload : [
                'text_hash' => $hash,
                'payload' => $payload,
            ];
            $value = $entry['payload'];
            $items[$this->key($projectId, $pageId, $langCode, $entry['text_hash'], $entry['type'] ?? 'text', $entry['attr'] ?? '', $entry['context_hash'] ?? '', $revision)] = is_string($value)
                ? ['translated' => $value, 'translation_id' => null, 'last_used_at' => null]
                : $value;
        }

        try {
            $cache->putMany($items, $ttl);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
