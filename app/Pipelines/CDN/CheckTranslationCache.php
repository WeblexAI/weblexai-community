<?php

namespace App\Pipelines\CDN;

use App\DTOs\CDN\TranslatedItemDTO;
use App\DTOs\CDN\TranslationContext;
use App\Services\Cache\TranslationCacheStore;
use Closure;

class CheckTranslationCache
{
    public function __construct(protected TranslationCacheStore $cache) {}

    public function handle(TranslationContext $context, Closure $next)
    {
        $translatables = collect($context->validated['translatables']);

        if ($translatables->isEmpty()) {
            return $next($context);
        }

        $hashMap = [];
        foreach ($translatables as $item) {
            $hash = $context->getTextHash($item['text']);
            $hashMap[$hash] = $item;
        }

        $hashes = array_keys($hashMap);

        $cached = $this->cache->getMany(
            $context->project->id,
            $context->page->id,
            $context->target,
            $hashes
        );

        foreach ($hashes as $hash) {
            $item = $hashMap[$hash];
            if (isset($cached[$hash]) && $cached[$hash] !== null) {
                $cachedItem = $cached[$hash];
                $shouldRefreshUsage = $context->markTranslationAsUsedIfStale(
                    $cachedItem['translation_id'],
                    $cachedItem['last_used_at'],
                );

                $dto = new TranslatedItemDTO(
                    id: $item['id'],
                    text: $item['text'],
                    translated: $cachedItem['translated'],
                    source: 'cache',
                    translationId: $cachedItem['translation_id'],
                );

                $context->cacheHits->push($dto);
                $context->translatedItems->push($dto);

                if ($shouldRefreshUsage) {
                    $this->cache->set(
                        $context->project->id,
                        $context->page->id,
                        $context->target,
                        $hash,
                        [
                            'translated' => $cachedItem['translated'],
                            'translation_id' => $cachedItem['translation_id'],
                            'last_used_at' => $context->usageTrackedAtIsoString(),
                        ],
                    );
                }
            } else {
                $context->needsDbLookup->push($item);
            }
        }

        $context->stream('cache', $context->cacheHits);

        return $next($context);
    }
}
