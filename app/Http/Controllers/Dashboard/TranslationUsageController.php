<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;

use App\Models\Project;
use App\Models\Translation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TranslationUsageController extends Controller
{
    private const RECENT_USAGE_DAYS = 5;

    public function index(Project $project): Response
    {
        $project->loadMissing('languages:id,name,iso_2', 'pages:id,project_id,origin,domain');
        $staleBefore = now()->subDays(self::RECENT_USAGE_DAYS);

        $baseQuery = Translation::query()
            ->where('project_id', $project->id)
            ->where('target_lang_id', '!=', $project->original_language_id);

        $tableQuery = (clone $baseQuery)->where(function (Builder $query) use ($staleBefore) {
            $query->whereNull('last_used_at')
                ->orWhere('last_used_at', '<', $staleBefore);
        });

        $translations = QueryBuilder::for($tableQuery)
            ->allowedFilters([
                AllowedFilter::callback('q', function (Builder $query, $input) {
                    if (is_array($input)) {
                        $input = implode(',', $input);
                    }

                    $query->where(function (Builder $nestedQuery) use ($input) {
                        $nestedQuery
                            ->where('text', 'LIKE', "%{$input}%")
                            ->orWhere('translated', 'LIKE', "%{$input}%");
                    });
                }),
                AllowedFilter::exact('page_id'),
                AllowedFilter::exact('language_id', 'target_lang_id'),
            ])
            ->with([
                'page:id,project_id,origin,domain',
                'targetLanguage:id,name,iso_2',
            ])
            ->orderByRaw('CASE WHEN last_used_at IS NULL THEN 0 ELSE 1 END')
            ->orderBy('last_used_at')
            ->orderByDesc('updated_at')
            ->paginate(perPage: 15)
            ->withQueryString()
            ->through(function (Translation $translation) use ($project) {
                $page = $translation->page;
                $language = $translation->targetLanguage;

                return [
                    'id' => $translation->id,
                    'text' => $translation->text,
                    'text_preview' => Str::limit($translation->text, 120),
                    'translated' => $translation->translated,
                    'translated_preview' => Str::limit($translation->translated, 120),
                    'quality' => $translation->quality,
                    'is_on' => $translation->is_on,
                    'is_reviewed' => $translation->is_reviewed,
                    'total_words' => $translation->total_words,
                    'last_used_at' => $translation->last_used_at?->toISOString(),
                    'page' => $page ? [
                        'id' => $page->id,
                        'path' => $page->path,
                        'origin' => $page->origin,
                        'domain' => $page->domain,
                    ] : null,
                    'language' => $language ? [
                        'id' => $language->id,
                        'name' => $language->name,
                        'iso_2' => $language->iso_2,
                    ] : null,
                    'manage_url' => $page && $language
                        ? route('projects.languages.show', [
                            'project' => $project->slug,
                            'language' => $language->iso_2,
                        ]).'?'.http_build_query(['page' => $page->domain])
                        : null,
                ];
            });

        return Inertia::render('Project/TranslationUsage', [
            'translations' => $translations,
            'pages' => $project->pages
                ->sortBy('origin')
                ->values()
                ->map(fn ($page) => [
                    'id' => $page->id,
                    'path' => $page->path,
                    'origin' => $page->origin,
                    'domain' => $page->domain,
                ]),
            'languages' => $project->languages
                ->sortBy('name')
                ->values()
                ->map(fn ($language) => [
                    'id' => $language->id,
                    'name' => $language->name,
                    'iso_2' => $language->iso_2,
                ]),
            'filters' => [
                'q' => request('filter.q'),
                'page_id' => request('filter.page_id'),
                'language_id' => request('filter.language_id'),
            ],
            'summary' => [
                'total' => (clone $baseQuery)->count(),
                'recently_used' => (clone $baseQuery)
                    ->where('last_used_at', '>=', $staleBefore)
                    ->count(),
                'stale' => (clone $baseQuery)
                    ->whereNotNull('last_used_at')
                    ->where('last_used_at', '<', $staleBefore)
                    ->count(),
                'never_used' => (clone $baseQuery)
                    ->whereNull('last_used_at')
                    ->count(),
            ],
            'recentUsageDays' => self::RECENT_USAGE_DAYS,
        ]);
    }
}

