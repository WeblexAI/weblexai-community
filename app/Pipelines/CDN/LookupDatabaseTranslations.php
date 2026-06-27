<?php

namespace App\Pipelines\CDN;

use App\DTOs\CDN\TranslatedItemDTO;
use App\DTOs\CDN\TranslationContext;
use App\Models\Translation;
use Closure;

class LookupDatabaseTranslations
{
    public function handle(TranslationContext $context, Closure $next)
    {
        if ($context->needsDbLookup->isEmpty()) {
            return $next($context);
        }

        $hashToItemMap = [];
        foreach ($context->needsDbLookup as $item) {
            $hash = $context->getTextHash($item['text']);
            $hashToItemMap[$hash] = $item;
        }

        foreach (collect(array_keys($hashToItemMap))->chunk(500) as $chunkHashes) {
            $dbTranslations = Translation::query()
                ->where('project_id', $context->project->id)
                ->where('page_id', $context->page->id)
                ->where('target_lang_id', $context->targetLanguage->id)
                ->where('is_on', true)
                ->whereIn('text_hash', $chunkHashes)
                ->get();

            foreach ($chunkHashes as $hash) {
                $item = $hashToItemMap[$hash];
                $dbTranslation = $dbTranslations->firstWhere('text_hash', $hash);

                if ($dbTranslation) {
                    $shouldRefreshUsage = $context->markTranslationAsUsedIfStale(
                        $dbTranslation->id,
                        $dbTranslation->last_used_at,
                    );

                    $dto = new TranslatedItemDTO(
                        id: $item['id'],
                        text: $item['text'],
                        translated: $dbTranslation->translated,
                        source: 'database',
                        translationId: $dbTranslation->id,
                    );

                    $context->translatedItems->push($dto);
                    $context->dbHits->push($dto);

                    $context->needsCaching->push([
                        'text' => $item['text'],
                        'text_hash' => $hash,
                        'translated' => $dbTranslation->translated,
                        'translation_id' => $dbTranslation->id,
                        'last_used_at' => $shouldRefreshUsage
                            ? $context->usageTrackedAtIsoString()
                            : $dbTranslation->last_used_at?->toISOString(),
                    ]);
                } else {
                    $context->needsNmtTranslation->push([
                        ...$item,
                        'total_words' => str_word_count($item['text']),
                    ]);
                }
            }
        }

        $context->stream('database', $context->dbHits);

        return $next($context);
    }
}
