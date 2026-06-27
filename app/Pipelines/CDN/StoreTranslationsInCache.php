<?php

namespace App\Pipelines\CDN;

use App\DTOs\CDN\TranslationContext;
use App\Services\Cache\TranslationCacheStore;
use Closure;

class StoreTranslationsInCache
{
    public function __construct(protected TranslationCacheStore $cache) {}

    public function handle(TranslationContext $context, Closure $next)
    {
        if ($context->needsCaching->isEmpty()) {
            return $next($context);
        }

        $toCache = [];

        foreach ($context->needsCaching as $item) {
            $toCache[$item['text_hash']] = [
                'translated' => $item['translated'],
                'translation_id' => $item['translation_id'] ?? null,
                'last_used_at' => $item['last_used_at'] ?? null,
            ];
        }

        $this->cache->setMany(
            $context->project->id,
            $context->page->id,
            $context->target,
            $toCache
        );

        return $next($context);
    }
}
