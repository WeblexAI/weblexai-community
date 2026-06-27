<?php

namespace App\Pipelines\CDN;

use App\DTOs\CDN\TranslationContext;
use Closure;

class ResolveLanguages
{
    public function handle(TranslationContext $context, Closure $next)
    {
        $project = $context->project;
        $project->loadMissing(['originalLanguage', 'languages']);

        if ($context->source !== $project->originalLanguage->iso_2) {
            $context->reset();
            $context->stoppageClass = self::class;

            return $context;
        }

        $context->targetLanguage = $project->languages()
            ->withPivot(['is_public', 'should_display_automatics', 'is_disabled'])
            ->select(['languages.id', 'languages.name', 'languages.iso_2'])
            ->where('iso_2', $context->target)
            ->where('languages.id', '!=', $project->original_language_id)
            ->first();

        if (! $context->targetLanguage
            || ! $context->targetLanguage->pivot->is_public
            || $context->targetLanguage->pivot->is_disabled) {
            $context->reset();
            $context->stoppageClass = self::class;

            return $context;
        }

        $context->targetLanguagePivot = $context->targetLanguage->pivot;

        return $next($context);
    }
}
