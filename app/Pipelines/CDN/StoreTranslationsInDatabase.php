<?php

namespace App\Pipelines\CDN;

use App\DTOs\CDN\TranslationContext;
use App\Jobs\CDN\StoreTranslationsJob;
use Closure;

class StoreTranslationsInDatabase
{
    public function handle(TranslationContext $context, Closure $next)
    {
        if ($context->nmtTranslated->isEmpty()) {
            return $next($context);
        }

        $results = $context->nmtTranslated->map(function ($dto) use ($context) {
            return [
                'id' => $dto->id,
                'text' => $dto->text,
                'translated' => $dto->translated,
                'source' => $context->sourceLanguage->iso_2,
                'target' => $context->targetLanguage->iso_2,
                'source_lang_id' => $context->sourceLanguage->id,
                'target_lang_id' => $context->targetLanguage->id,
                'request' => ['id' => $dto->id, 'text' => $dto->text],
            ];
        })->toArray();

        StoreTranslationsJob::dispatch(
            $results,
            $context->page->id,
            $context->project->id,
        );

        return $next($context);
    }
}
