<?php

namespace App\Pipelines\CDN;

use App\DTOs\CDN\TranslationContext;
use App\Enums\TranslationModelType;
use Closure;

class DetermineTranslationModel
{
    public function handle(TranslationContext $context, Closure $next)
    {
        $project = $context->project;
        $credential = $project->providerCredential;

        if (! $credential || ! $credential->is_active) {
            throw new \RuntimeException('No active translation provider is assigned to this project.');
        }

        $context->useModel = $credential->provider->type();
        $context->llmOptions = $context->useModel === TranslationModelType::LLM
            ? array_filter([
                'context' => $project->website_description,
                'tone' => $project->translation_tone?->value,
                'audience' => $project->translation_audience?->value,
            ])
            : [];

        return $next($context);
    }
}
