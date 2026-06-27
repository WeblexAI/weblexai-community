<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\TranslationQuality;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Language\AttachLanguageToProjectRequest;
use App\Http\Requests\Dashboard\Language\ToggleLanguageTranslationsAutomaticsRequest;
use App\Http\Requests\Dashboard\Language\ToggleLanguageTranslationsPublicityRequest;
use App\Models\Language;
use App\Models\Project;
use App\Services\ProjectService;
use App\Services\TranslationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ProjectLanguageController extends Controller
{
    public function index(Project $project): Response
    {
        $languages = $project->languages()
            ->withPivot(['is_public', 'should_display_automatics', 'is_disabled'])
            ->get();

        if ($languages->isNotEmpty()) {
            $stats = DB::table('translations')
                ->where('project_id', $project->id)
                ->whereIn('target_lang_id', $languages->pluck('id'))
                ->selectRaw(
                    'target_lang_id, COALESCE(SUM(total_words), 0) as total_translated_words, '.
                    'COALESCE(SUM(CASE WHEN quality = ? THEN total_words ELSE 0 END), 0) as manual_translated_words',
                    [TranslationQuality::MANUAL->value],
                )
                ->groupBy('target_lang_id')
                ->get()
                ->keyBy('target_lang_id');

            foreach ($languages as $language) {
                $stat = $stats->get($language->id);
                $language->total_translated_words = (int) ($stat?->total_translated_words ?? 0);
                $language->manual_translated_words = (int) ($stat?->manual_translated_words ?? 0);
            }
        }

        $excludedIds = $languages->pluck('id')->push($project->original_language_id);

        return Inertia::render('Project/Languages', [
            'languages' => $languages,
            'languagesToAttach' => Language::query()->whereNotIn('id', $excludedIds)->get(),
            'indexPage' => $project->indexPage(),
        ]);
    }

    public function show(Project $project, Language $language): Response
    {
        $pageDomain = request()->string('page')->toString();
        abort_if($pageDomain === '', 404);

        $project->loadMissing(['originalLanguage', 'pages']);
        $page = $project->pages()
            ->where('domain', $pageDomain)
            ->withCount(['translations' => fn ($query) => $query->where('target_lang_id', $language->id)])
            ->firstOrFail();

        return Inertia::render('Project/LanguageTranslations', [
            'language' => $language,
            'languages' => $project->languages()
                ->where('languages.id', '!=', $language->id)
                ->get(['languages.id', 'name', 'iso_2']),
            'originalLanguage' => $project->originalLanguage,
            'pages' => $project->pages,
            'page' => $page,
            'translations' => TranslationService::applyFilter($language, $page),
            'indexPage' => $project->indexPage(),
        ]);
    }

    public function attachLanguage(Project $project, AttachLanguageToProjectRequest $request): RedirectResponse
    {
        $this->authorize('manage', [Language::class, $project]);

        try {
            DB::transaction(fn () => ProjectService::attachLanguage($project, $request->validated()));

            return back()->with(successRes('Language attached to project.'));
        } catch (\Throwable $exception) {
            Log::error($exception);

            return back()->with(errorRes());
        }
    }

    public function detachLanguage(Project $project, Language $language): RedirectResponse
    {
        $this->authorize('manage', [Language::class, $project]);

        try {
            DB::transaction(fn () => ProjectService::detachLanguage($project, $language));

            return back()->with(successRes('Language removed from project.'));
        } catch (\Throwable $exception) {
            Log::error($exception);

            return back()->with(errorRes());
        }
    }

    public function enableLanguage(Project $project, Language $language): RedirectResponse
    {
        $this->authorize('manage', [Language::class, $project]);

        if (! $project->languages()->where('language_id', $language->id)->wherePivot('is_disabled', true)->exists()) {
            return response()->error('Language is already enabled or not attached.');
        }

        ProjectService::updateLanguagePivot($project, $language, [
            'is_disabled' => false,
            'disabled_at' => null,
            'disabled_reason' => null,
        ]);

        return response()->success("{$language->name} enabled.");
    }

    public function disableLanguage(Project $project, Language $language): RedirectResponse
    {
        $this->authorize('manage', [Language::class, $project]);

        if (! $project->languages()->where('language_id', $language->id)->wherePivot('is_disabled', false)->exists()) {
            return response()->error('Language is already disabled or not attached.');
        }

        ProjectService::updateLanguagePivot($project, $language, [
            'is_disabled' => true,
            'disabled_at' => now(),
            'disabled_reason' => 'Manually disabled',
        ]);

        return response()->success("{$language->name} disabled.");
    }

    public function togglePublicity(
        Project $project,
        Language $language,
        ToggleLanguageTranslationsPublicityRequest $request,
    ): RedirectResponse {
        $this->authorize('manage', [Language::class, $project]);
        $isPublic = $request->validated('is_public');
        ProjectService::updateLanguagePivot($project, $language, ['is_public' => $isPublic]);

        return back()->with(successRes($isPublic ? 'Translations are visible.' : 'Translations are hidden.'));
    }

    public function toggleAutomatics(
        Project $project,
        Language $language,
        ToggleLanguageTranslationsAutomaticsRequest $request,
    ): RedirectResponse {
        $this->authorize('manage', [Language::class, $project]);
        $enabled = $request->validated('should_display_automatics');
        ProjectService::updateLanguagePivot($project, $language, ['should_display_automatics' => $enabled]);

        return back()->with(successRes(
            $enabled ? 'Automatic translations are visible.' : 'Automatic translations are hidden.'
        ));
    }
}
