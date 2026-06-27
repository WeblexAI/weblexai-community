<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;

use App\Enums\TranslationQuality;
use App\Http\Requests\Dashboard\Page\ToggleBulkBlacklistRequest;
use App\Models\Language;
use App\Models\Page;
use App\Models\Project;
use App\Services\PageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PageController extends Controller
{
    public function index(Project $project, Language $language): Response
    {
        $project->loadMissing('originalLanguage');

        $pages = QueryBuilder::for(Page::class)
            ->allowedFilters([
                AllowedFilter::callback('q', function (Builder $query, $input) {
                    // in case q contains "," , spatie query builder treats it as array
                    if (is_array($input)) {
                        $input = implode(',', $input);
                    }
                    $query->where('title', 'LIKE', "%$input%")
                        ->orWhere('domain', 'LIKE', "%$input%");
                }),
                AllowedFilter::callback('status', function (Builder $query, $input) {
                    if ($input !== 'active' && $input !== 'blacklisted') {
                        return;
                    }
                    $query->where('is_blacklisted', $input === 'blacklisted');
                }),
            ])
            ->where('project_id', $project->id)
            ->withCount(['translations as total_translated_words' => function (Builder $translationQuery) use ($language) {
                $translationQuery
                    ->where('target_lang_id', $language->id);
            }])
            ->withSum(['translations as manual_translated_words' => function (Builder $translationQuery) use ($language) {
                $translationQuery
                    ->where('quality', TranslationQuality::MANUAL)
                    ->where('target_lang_id', $language->id);
            }], 'total_words')
            ->paginate(perPage: 10, pageName: 'ppage')
            ->appends(request()->query());

        $languages = $project->languages()
            ->whereNot('languages.id', $language->id)
            ->get(['languages.id', 'name', 'iso_2']);

        return Inertia::render('Project/Pages', [
            'language' => $language,
            'languages' => $languages,
            'originalLanguage' => $project->originalLanguage,
            'pages' => $pages,
        ]);
    }

    public function toggleBlacklist(Project $project, Page $page): RedirectResponse
    {
        $this->authorize('toggleBlacklist', $page);

        try {
            $page = PageService::toggleBlacklist($page);
            $message = $page->is_blacklisted ? 'Page added to blacklist.' : 'Page removed from blacklist.';

            return response()->success($message);
        } catch (\Exception $exception) {
            return response()->error();
        }
    }

    public function toggleBulkBlacklist(Project $project, ToggleBulkBlacklistRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        try {
            $pagesCount = count($validated['page_ids']);
            PageService::toggleBulkBlacklist($project, $validated);
            $message = $validated['is_blacklisted'] ? "$pagesCount page(s) added to blacklist." : "$pagesCount page(s) removed from blacklist.";

            return response()->success($message);
        } catch (\Exception $exception) {
            return response()->error();
        }
    }
}

