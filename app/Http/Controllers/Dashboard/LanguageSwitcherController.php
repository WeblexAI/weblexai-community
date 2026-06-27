<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;

use App\Http\Requests\Dashboard\UpdateLanguageSwitcherRequest;
use App\Models\LanguageSwitcherConfig;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LanguageSwitcherController extends Controller
{
    public function index(Project $project): Response
    {
        $this->authorize('view', [LanguageSwitcherConfig::class, $project]);

        return Inertia::render('Project/LanguageSwitcher', [
            'config' => $project->languageSwitcherConfig,
        ]);
    }

    public function update(Project $project, UpdateLanguageSwitcherRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        if (! $validated['should_display_name'] && ! $validated['should_display_flag']) {
            return response()->error('Display either the language name or flag.');
        }

        try {
            DB::transaction(fn () => ProjectService::updateLanguageSwitcher($project, $validated));

            return response()->success('Switcher configuration updated.');
        } catch (\Throwable) {
            return response()->error();
        }
    }
}

