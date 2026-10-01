<?php

namespace App\Pipelines\CDN;

use App\DTOs\CDN\TranslationContext;
use App\Models\Page;
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

        $page = Page::query()->firstOrCreate(
            ['project_id' => $project->id, 'domain' => $domain],
            ['title' => $pageTitle, 'origin' => $pageOrigin, 'is_active' => true, 'is_blacklisted' => false],
        );

        if ($page->is_blacklisted || ! $page->is_active) {
            $context->reset();
            $context->stoppageClass = self::class;

            return $context;
        }

        if (! $page->wasRecentlyCreated) {
            $page->fill(['title' => $pageTitle])->saveQuietly();
        }

        $context->page = $page;

        return $next($context);
    }
}
