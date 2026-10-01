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

        $identityMap = [];
        foreach ($translatables as $item) {
            $hash = $context->getTextHash($item['text']);
            $contextHash = $item['context'] === '' ? '' : $context->getContextHash($item['context']);
            $identity = implode('|', [$item['type'], $item['attr'], $contextHash, $hash]);
            $identityMap[$identity] ??= ['hash' => $hash, 'contextHash' => $contextHash, 'items' => []];
            $identityMap[$identity]['items'][] = $item;
        }

        $identities = array_keys($identityMap);

        $cached = $this->cache->getMany(
            $context->project->id,
            $context->page->id,
            $context->target,
            array_map(fn (array $entry): array => [
                'text_hash' => $entry['hash'],
                'context_hash' => $entry['contextHash'],
                'type' => $entry['items'][0]['type'],
                'attr' => $entry['items'][0]['attr'],
            ], $identityMap),
            $context->project->delivery_revision,
        );

        foreach ($identities as $identity) {
            $entry = $identityMap[$identity];
            $items = $entry['items'];
            if (isset($cached[$identity]) && $cached[$identity] !== null) {
                $cachedItem = $cached[$identity];
                $shouldRefreshUsage = $context->markTranslationAsUsedIfStale(
                    $cachedItem['translation_id'],
                    $cachedItem['last_used_at'],
                );

                foreach ($items as $item) {
                    $dto = new TranslatedItemDTO(
                        id: $item['id'],
                        text: $item['text'],
                        translated: $cachedItem['translated'],
                        source: 'cache',
                        translationId: $cachedItem['translation_id'],
                        type: $item['type'],
                        attr: $item['attr'],
                        context: $item['context'],
                    );

                    $context->cacheHits->push($dto);
                    $context->translatedItems->push($dto);
                }

                if ($shouldRefreshUsage) {
                    $this->cache->set(
                        $context->project->id,
                        $context->page->id,
                        $context->target,
                        $entry['hash'],
                        [
                            'translated' => $cachedItem['translated'],
                            'translation_id' => $cachedItem['translation_id'],
                            'last_used_at' => $context->usageTrackedAtIsoString(),
                        ],
                        $items[0]['type'],
                        $items[0]['attr'],
                        $entry['contextHash'],
                        $context->project->delivery_revision,
                    );
                }
            } else {
                foreach ($items as $item) {
                    $context->needsDbLookup->push($item);
                }
            }
        }

        $context->stream('cache', $context->cacheHits);

        return $next($context);
    }
}
