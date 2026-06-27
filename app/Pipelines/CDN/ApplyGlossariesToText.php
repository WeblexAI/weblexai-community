<?php

namespace App\Pipelines\CDN;

use App\DTOs\CDN\TranslationContext;
use App\Services\GlossaryService;
use Closure;

class ApplyGlossariesToText
{
    public function __construct(protected GlossaryService $glossaryService) {}

    public function handle(TranslationContext $context, Closure $next)
    {
        if ($context->needsNmtTranslation->isEmpty()) {
            return $next($context);
        }

        $glossaries = $this->glossaryService->getProjectGlossaries(
            $context->project,
            $context->targetLanguage
        );

        if ($glossaries->isEmpty()) {
            return $next($context);
        }

        $appliedGlossariesMap = [];
        $context->needsNmtTranslation = $context->needsNmtTranslation->map(
            function ($item) use ($glossaries, &$appliedGlossariesMap) {
                $result = $this->glossaryService->applyToText(
                    $item['text'],
                    $glossaries
                );

                $appliedGlossariesMap[$item['id']] = $result['applied_glossaries'];
                $item['text'] = $result['text'];

                return $item;
            }
        );

        $context->appliedGlossaries = $appliedGlossariesMap;

        return $next($context);
    }
}
