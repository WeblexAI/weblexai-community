<?php

namespace App\Pipelines\CDN;

use App\DTOs\CDN\TranslationContext;
use App\Pivots\ProjectLanguagePivot;
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

        $pivot = $context->targetLanguage?->pivot;

        if (! $context->targetLanguage
            || ! $pivot instanceof ProjectLanguagePivot
            || ! $pivot->is_public
            || $pivot->is_disabled) {
            $context->reset();
            $context->stoppageClass = self::class;

            return $context;
        }

        $context->targetLanguagePivot = $pivot;

        return $next($context);
    }
}
