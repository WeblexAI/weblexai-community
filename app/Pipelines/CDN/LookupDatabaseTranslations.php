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

        $identityToItemMap = [];
        foreach ($context->needsDbLookup as $item) {
            $hash = $context->getTextHash($item['text']);
            $contextHash = $item['context'] === '' ? '' : $context->getContextHash($item['context']);
            $identityToItemMap[] = compact('item', 'hash', 'contextHash');
        }

        foreach (collect($identityToItemMap)->chunk(500) as $chunk) {
            $dbTranslations = Translation::query()
                ->where('project_id', $context->project->id)
                ->where('page_id', $context->page->id)
                ->where('target_lang_id', $context->targetLanguage->id)
                ->whereIn('text_hash', $chunk->pluck('hash')->unique())
                ->get();

            foreach ($chunk as $entry) {
                $item = $entry['item'];
                $dbTranslation = $dbTranslations->first(fn (Translation $translation): bool => $translation->text_hash === $entry['hash']
                    && (string) $translation->context_hash === $entry['contextHash']
                    && $translation->type?->value === $item['type']
                    && (string) $translation->attr === $item['attr']
                );

                $canDisplayAutomatic = $context->project->should_display_automatics
                    && $context->targetLanguagePivot->should_display_automatics;
                $canDeliver = $dbTranslation
                    && $dbTranslation->is_on
                    && ! $dbTranslation->needs_regeneration
                    && ($canDisplayAutomatic || $dbTranslation->is_reviewed);

                if ($canDeliver) {
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
                        type: $item['type'],
                        attr: $item['attr'],
                        context: $item['context'],
                    );

                    $context->translatedItems->push($dto);
                    $context->dbHits->push($dto);

                    $context->needsCaching->push([
                        'text' => $item['text'],
                        'text_hash' => $entry['hash'],
                        'context_hash' => $entry['contextHash'],
                        'type' => $item['type'],
                        'attr' => $item['attr'],
                        'translated' => $dbTranslation->translated,
                        'translation_id' => $dbTranslation->id,
                        'last_used_at' => $shouldRefreshUsage
                            ? $context->usageTrackedAtIsoString()
                            : $dbTranslation->last_used_at?->toISOString(),
                    ]);
                } elseif (! $dbTranslation || ($dbTranslation->is_on && $dbTranslation->needs_regeneration)) {
                    $context->needsNmtTranslation->push([
                        ...$item,
                        'original_text' => $item['text'],
                        'original_context' => $item['context'],
                        'total_words' => str_word_count($item['text']),
                    ]);
                } else {
                    $context->withheldIds->push((string) $item['id']);
                }
            }
        }

        $context->stream('database', $context->dbHits);

        return $next($context);
    }
}
