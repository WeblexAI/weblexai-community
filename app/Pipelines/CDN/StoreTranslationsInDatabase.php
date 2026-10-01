<?php

namespace App\Pipelines\CDN;

use App\DTOs\CDN\TranslatedItemDTO;
use App\DTOs\CDN\TranslationContext;
use App\Enums\TranslationQuality;
use App\Enums\TranslationType;
use App\Models\Project;
use App\Models\Translation;
use App\Support\TextHasher;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoreTranslationsInDatabase
{
    public function handle(TranslationContext $context, Closure $next)
    {
        if ($context->nmtTranslated->isEmpty()) {
            return $next($context);
        }

        $stored = DB::transaction(function () use ($context): array {
            $project = Project::query()->lockForUpdate()->findOrFail($context->project->id);
            if ((int) $project->generation_revision !== $context->generationRevision
                || (int) $project->delivery_revision !== (int) $context->project->delivery_revision) {
                throw new \RuntimeException('The translation configuration changed while this request was running.');
            }

            $translations = [];
            foreach ($context->nmtTranslated as $dto) {
                $contextHash = $dto->context === '' ? '' : TextHasher::hash($dto->context);
                $identity = [
                    'project_id' => $project->id,
                    'page_id' => $context->page->id,
                    'target_lang_id' => $context->targetLanguage->id,
                    'type' => TranslationType::from($dto->type)->value,
                    'attr' => $dto->attr,
                    'text_hash' => TextHasher::hash($dto->text),
                    'context_hash' => $contextHash,
                ];
                $existing = Translation::query()->where($identity)->lockForUpdate()->first();

                if ($existing && ! $existing->needs_regeneration) {
                    $translations[$dto->id] = $existing;

                    continue;
                }

                $attributes = [
                    ...$identity,
                    'text' => $dto->text,
                    'source_context' => $dto->context,
                    'translated' => $dto->translated,
                    'total_words' => str_word_count($dto->text),
                    'source_lang_id' => $context->sourceLanguage->id,
                    'quality' => TranslationQuality::AUTOMATIC,
                    'is_reviewed' => false,
                    'needs_regeneration' => false,
                    'last_used_at' => $context->usageTrackedAt,
                ];

                if ($existing) {
                    Translation::withoutEvents(fn () => $existing->update($attributes));
                    $translations[$dto->id] = $existing->fresh();
                } else {
                    $translations[$dto->id] = Translation::query()->create([
                        ...$attributes,
                        'uuid' => Str::uuid()->toString(),
                        'is_on' => true,
                    ]);
                }
            }

            return $translations;
        });

        $context->nmtTranslated = $context->nmtTranslated->map(function ($dto) use ($stored, $context) {
            $translation = $stored[$dto->id] ?? null;

            if (! $translation || ! $translation->is_on || $translation->needs_regeneration
                || (! ($context->project->should_display_automatics && $context->targetLanguagePivot->should_display_automatics) && ! $translation->is_reviewed)) {
                $context->withheldIds->push((string) $dto->id);

                return null;
            }

            $context->needsCaching->push([
                'text_hash' => $translation->text_hash,
                'context_hash' => $translation->context_hash,
                'type' => $dto->type,
                'attr' => $dto->attr,
                'translated' => $translation->translated,
                'translation_id' => $translation->id,
                'last_used_at' => $translation->last_used_at?->toISOString(),
            ]);

            return $translation ? new TranslatedItemDTO(
                id: $dto->id,
                text: $dto->text,
                translated: $translation->translated,
                source: 'nmt',
                translationId: $translation->id,
                type: $dto->type,
                attr: $dto->attr,
                context: $dto->context,
            ) : $dto;
        })->filter()->values();

        $context->translatedItems = $context->translatedItems->concat($context->nmtTranslated);

        return $next($context);
    }
}
