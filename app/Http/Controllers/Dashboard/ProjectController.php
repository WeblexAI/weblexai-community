<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;

use App\Enums\TranslationQuality;
use App\Http\Requests\Dashboard\UpdateProjectRequest;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\QueryBuilder;

class ProjectController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        $projects = QueryBuilder::for(Project::class)
            ->withCount('languages')
            ->withCount([
                'translations as manual_translations_count' => fn ($query) => $query
                    ->where('quality', TranslationQuality::MANUAL),
            ])
            ->withSum('translations', 'total_words')
            ->where(function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->orWhereHas('collaborators', fn ($members) => $members->where('users.id', $user->id));
            })
            ->paginate()
            ->appends(request()->query());

        return Inertia::render('Projects', ['projects' => $projects]);
    }

    public function update(Project $project, UpdateProjectRequest $request): RedirectResponse
    {
        try {
            DB::transaction(fn () => ProjectService::update($project, $request->validated()));

            return to_route('projects.settings', $project)
                ->with(successRes('Project updated.'));
        } catch (\Throwable $exception) {
            Log::error($exception);

            return back()->with(errorRes());
        }
    }

    public function settings(Project $project): Response
    {
        $this->authorize('update', $project);

        return Inertia::render('Project/Settings');
    }
}

