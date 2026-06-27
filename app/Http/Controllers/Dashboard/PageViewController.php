<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;

use App\Models\Page;
use App\Models\Project;
use App\Models\View;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PageViewController extends Controller
{
    public function index(Project $project): Response
    {
        [$dateFrom, $dateTo] = getViewsAndTranslRequestsQueryDate();

        $pageViewsByCountry = DB::table('views')
            ->whereBetween('viewed_at', [$dateFrom, $dateTo])
            ->select('country as name', DB::raw('COUNT(*) as value'))
            ->where('project_id', $project->id)
            ->where('viewable_type', Page::class)
            ->groupBy('country')
            ->get();

        $projectLanguages = $project->languages()->pluck('languages.id')->toArray();
        $projectLanguages = [...$projectLanguages, $project->original_language_id];

        $pageViewsByLanguage = View::query()
            ->whereBetween('viewed_at', [$dateFrom, $dateTo])
            ->with(['browserLanguage:languages.id,name'])
            ->select('browser_lang_id', DB::raw('COUNT(*) as views_count'))
            ->where('project_id', $project->id)
            ->where('viewable_type', Page::class)
            ->groupBy('browser_lang_id')
            ->get()
            ->map(function ($view) use ($projectLanguages) {
                return [
                    'language_id' => $view->browser_lang_id,
                    'language_name' => optional($view->browserLanguage)->name,
                    'is_added_to_project' => in_array($view->browser_lang_id, $projectLanguages),
                    'views_count' => $view->views_count,
                ];
            });

        return Inertia::render('Project/PageViews', [
            'pageViewsByCountry' => $pageViewsByCountry,
            'pageViewsByLanguage' => $pageViewsByLanguage,
        ]);
    }
}

