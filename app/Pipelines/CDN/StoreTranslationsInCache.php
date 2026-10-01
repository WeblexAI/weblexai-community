<?php

namespace App\Pipelines\CDN;

use App\DTOs\CDN\TranslationContext;
use App\Services\Cache\TranslationCacheStore;
use Closure;

class StoreTranslationsInCache
{
    public function __construct(private readonly TranslationCacheStore $cache) {}

    public function handle(TranslationContext $context, Closure $next)
    {
        $items = $context->needsCaching->map(fn (array $item): array => [
            'text_hash' => $item['text_hash'],
            'context_hash' => $item['context_hash'] ?? '',
            'type' => $item['type'] ?? 'text',
            'attr' => $item['attr'] ?? '',
            'payload' => [
                'translated' => $item['translated'],
                'translation_id' => $item['translation_id'] ?? null,
                'last_used_at' => $item['last_used_at'] ?? null,
            ],
        ])->all();

        try {
            $this->cache->setMany($context->project->id, $context->page->id, $context->target, $items, $context->project->delivery_revision);
        } catch (\Throwable $exception) {
            report($exception);
        }

        $context->needsCaching = collect();
        $context->stream('nmt', $context->nmtTranslated);

        return $next($context);
    }
}
