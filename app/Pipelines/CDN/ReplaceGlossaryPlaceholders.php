<?php

namespace App\Pipelines\CDN;

use App\DTOs\CDN\TranslatedItemDTO;
use App\DTOs\CDN\TranslationContext;
use App\Services\GlossaryService;
use Closure;

class ReplaceGlossaryPlaceholders
{
    public function __construct(protected GlossaryService $glossaryService) {}

    public function handle(TranslationContext $context, Closure $next)
    {
        $processedNmtItems = collect();

        if (empty($context->appliedGlossaries)) {
            foreach ($context->nmtTranslated as $dto) {
                $context->translatedItems->push($dto);
                $processedNmtItems->push($dto);

                $context->needsCaching->push([
                    'text' => $dto->text,
                    'text_hash' => $context->getTextHash($dto->text),
                    'translated' => $dto->translated,
                    'translation_id' => null,
                    'last_used_at' => now()->toISOString(),
                ]);
            }

            $context->stream('nmt', $processedNmtItems);

            return $next($context);
        }

        foreach ($context->nmtTranslated as $key => $dto) {
            $itemGlossaries = $context->appliedGlossaries[$dto->id] ?? [];
            if (! empty($itemGlossaries)) {
                $replacedText = $this->glossaryService->replacePlaceholders(
                    $dto->translated,
                    $itemGlossaries
                );

                $dto = new TranslatedItemDTO(
                    id: $dto->id,
                    text: $dto->text,
                    translated: $replacedText,
                    source: $dto->source,
                    translationId: $dto->translationId,
                );

                $context->nmtTranslated[$key] = $dto;
            }

            $context->translatedItems->push($dto);
            $processedNmtItems->push($dto);

            $context->needsCaching->push([
                'text' => $dto->text,
                'text_hash' => $context->getTextHash($dto->text),
                'translated' => $dto->translated,
                'translation_id' => null,
                'last_used_at' => now()->toISOString(),
            ]);
        }

        $context->stream('nmt', $processedNmtItems);

        return $next($context);
    }
}
