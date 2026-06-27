<?php

namespace App\Pipelines\CDN;

use App\DTOs\CDN\TranslationContext;
use App\Support\UrlHelper;
use Closure;

class ResolvePage
{
    public function handle(TranslationContext $context, Closure $next)
    {
        $project = $context->project;
        $pageUrl = request()->attributes->get('pageUrl');
        $pageTitle = request()->attributes->get('pageTitle');

        [$pageOrigin, $domain] = app(UrlHelper::class)->getDomainAndOrigin($pageUrl);

        $page = $project->pages()->firstOrCreate(
            ['domain' => $domain],
            ['title' => $pageTitle, 'origin' => $pageOrigin],
        );

        if ($page->is_blacklisted) {
            $context->reset();
            $context->stoppageClass = self::class;

            return $context;
        }

        if (! $page->wasRecentlyCreated) {
            $page->update(['title' => $pageTitle]);
        }

        $context->page = $page;

        return $next($context);
    }
}
