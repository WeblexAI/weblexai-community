<?php

namespace App\Pipelines\CDN;

use App\DTOs\CDN\TranslatedItemDTO;
use App\DTOs\CDN\TranslationContext;
use App\Services\GlossaryService;
use Closure;

class ReplaceGlossaryPlaceholders
{
    public function __construct(private readonly GlossaryService $glossaryService) {}

    public function handle(TranslationContext $context, Closure $next)
    {
        $context->nmtTranslated = $context->nmtTranslated->map(function (TranslatedItemDTO $dto) use ($context): TranslatedItemDTO {
            $glossaries = $context->appliedGlossaries[$dto->id] ?? [];

            return new TranslatedItemDTO(
                id: $dto->id,
                text: $dto->text,
                translated: $glossaries === [] ? $dto->translated : $this->glossaryService->replacePlaceholders($dto->translated, $glossaries),
                source: $dto->source,
                translationId: $dto->translationId,
                type: $dto->type,
                attr: $dto->attr,
                context: $dto->context,
            );
        });

        return $next($context);
    }
}
