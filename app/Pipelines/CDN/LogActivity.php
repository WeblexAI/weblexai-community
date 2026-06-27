<?php

namespace App\Pipelines\CDN;

use App\DTOs\CDN\TranslationContext;
use Closure;

class LogActivity
{
    public function handle(TranslationContext $context, Closure $next)
    {
        $context->project->translationRequests()->create([
            'page_id' => $context->page->id,
            'source_lang_id' => $context->sourceLanguage->id,
            'target_lang_id' => $context->targetLanguage->id,
            'ip' => request()->ip(),
        ]);

        $cooler = now()->addHours(3);
        $recent = $context->page->views()
            ->where('ip_address', request()->ip())
            ->where('target_lang_id', $context->targetLanguage->id)
            ->where('viewed_at', '>=', now()->subHours(3))
            ->exists();

        if (! $recent) {
            views($context->page)->cooldown($cooler)->record();
        }

        return $next($context);
    }
}
