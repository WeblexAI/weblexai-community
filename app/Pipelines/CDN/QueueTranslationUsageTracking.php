<?php

namespace App\Pipelines\CDN;

use App\DTOs\CDN\TranslationContext;
use App\Jobs\CDN\UpdateTranslationsLastUsedAtJob;
use Closure;

class QueueTranslationUsageTracking
{
    public function handle(TranslationContext $context, Closure $next)
    {
        $result = $next($context);

        if ($context->translationIdsToTouch->isNotEmpty()) {
            UpdateTranslationsLastUsedAtJob::dispatch(
                $context->translationIdsToTouch->unique()->values()->all(),
                $context->usageTrackedAtIsoString(),
            );
        }

        return $result;
    }
}
