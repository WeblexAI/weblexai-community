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
            try {
                UpdateTranslationsLastUsedAtJob::dispatch(
                    $context->translationIdsToTouch->unique()->values()->all(),
                    $context->usageTrackedAtIsoString(),
                );
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return $result;
    }
}
