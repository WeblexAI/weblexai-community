<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;

use App\Enums\TranslationQuality;
use App\Models\ActivityLog;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Number;
use Inertia\Inertia;
use Inertia\Response;

class ProjectOverviewController extends Controller
{
    public function overview(Project $project): Response
    {
        return Inertia::render('Project/Overview');
    }

    public function getCollaborators(Project $project): JsonResponse
    {
        return $this->jsonSuccess([
            'members' => $project->collaborators()->orderBy('name')->get(),
        ]);
    }

    public function getProjectDetails(Project $project): JsonResponse
    {
        return $this->jsonSuccess([
            'languagesCount' => Number::format($project->languages()->count()),
            'translationsCount' => Number::format($project->translations()->count()),
            'translatedWordsCount' => Number::format((int) $project->translations()->sum('total_words')),
            'manualTranslationsCount' => Number::format(
                $project->translations()->where('quality', TranslationQuality::MANUAL)->count()
            ),
            'glossariesCount' => Number::format($project->glossaries()->count()),
            'excludedBlocksCount' => Number::format($project->excludedBlocks()->count()),
            'blacklistedPagesCount' => Number::format(
                $project->pages()->where('is_blacklisted', true)->count()
            ),
        ]);
    }

    public function getActivityLogs(Project $project): JsonResponse
    {
        $this->authorize('viewActivityLogs', $project);

        return $this->jsonSuccess([
            'activities' => ActivityLog::query()
                ->where('project_id', $project->id)
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}

