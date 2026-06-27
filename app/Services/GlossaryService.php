<?php

namespace App\Services;

use App\Enums\GlossaryRule;
use App\Enums\ModelStatus;
use App\Models\Glossary;
use App\Models\Language;
use App\Models\Project;
use App\Services\Cache\ProjectCacheInvalidationService;
use App\Settings\CacheSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class GlossaryService
{
    public static function getRegistryKey(int $projectId): string
    {
        return "glossary:project:{$projectId}:keys";
    }

    public static function store(Project $project, array $data): Glossary
    {
        $languages = $data['languages'] ?? [];
        $is_all_languages = count($languages) < 1;
        $rule = GlossaryRule::from($data['rule']);
        $is_case_sensitive = $data['is_case_sensitive'];

        $glossary = Glossary::query()->create([
            'project_id' => $project->id,
            'text' => $data['text'],
            'translated' => $rule === GlossaryRule::ALWAYS_TRANSLATE ? $data['translated'] : $data['text'],
            'is_case_sensitive' => $is_case_sensitive,
            'is_all_languages' => $is_all_languages,
            'rule' => $rule,
        ]);

        if (! $is_all_languages) {
            $glossary->languages()->attach($languages);
        }

        defer(function () use ($project, $data, $is_all_languages, $languages, $is_case_sensitive) {
            $project->translations()
                ->whereTextContains($data['text'], $is_case_sensitive)
                ->when(! $is_all_languages, function (Builder $query) use ($languages) {
                    $query->whereIn('target_lang_id', $languages);
                })->delete();

            app(ProjectCacheInvalidationService::class)->clearProject(
                $project->id,
                config: false,
                translations: true,
            );
        });

        return $glossary;
    }

    public static function update(Project $project, Glossary $glossary, array $data): Glossary
    {
        if (! $glossary->project->is($project)) {
            return $glossary;
        }

        $languages = $data['languages'] ?? [];
        $is_all_languages = count($languages) < 1;
        $rule = GlossaryRule::from($data['rule']);

        $glossary->update([
            'translated' => $rule === GlossaryRule::ALWAYS_TRANSLATE ? $data['translated'] : null,
            'is_case_sensitive' => $data['is_case_sensitive'],
            'is_all_languages' => $is_all_languages,
            'rule' => $rule,
        ]);

        if (! $is_all_languages) {
            $glossary->languages()->syncWithoutDetaching($languages);
        }

        return $glossary;
    }

    public static function delete(Project $project, Glossary $glossary): void
    {
        if (! $glossary->project->is($project)) {
            return;
        }

        defer(function () use ($project, $glossary) {
            $languages = $project->languages()->pluck('languages.id')->toArray();

            $project
                ->translations()
                ->whereTranslatedContains($glossary->translated, $glossary->is_case_sensitive)
                ->when(! $glossary->is_all_languages, function (Builder $query) use ($languages) {
                    $query->whereIn('target_lang_id', $languages);
                })
                ->delete();

            app(ProjectCacheInvalidationService::class)->clearProject(
                $project->id,
                config: false,
                translations: true,
            );
        });

        $glossary->languages()->detach();
        $glossary->delete();
    }

    public static function deleteBulk(Project $project, array $ids): void
    {
        $glossaries = $project->glossaries()->whereIn('id', $ids)->get();
        foreach ($glossaries as $glossary) {
            self::delete($project, $glossary);
        }
    }

    public static function getCacheKey(int $projectId, string $langCode): string
    {
        return "glossary:project:{$projectId}:lang:{$langCode}";
    }

    public function getProjectGlossaries(Project $project, Language $language): Collection
    {
        $cacheKey = self::getCacheKey($project->id, $language->iso_2);
        self::registerCacheKey($project->id, $language->iso_2);

        return Cache::remember(
            $cacheKey,
            app(CacheSettings::class)->getGlossaryTtlInSeconds(),
            fn () => $project->glossaries()
                ->where('is_active', ModelStatus::ACTIVE)
                ->where(function (Builder $query) use ($language) {
                    $query->where('is_all_languages', true)
                        ->orWhereHas('languages', fn (Builder $languageQuery) => $languageQuery
                            ->where('languages.id', $language->id));
                })
                ->orderByRaw('LENGTH("text") DESC')
                ->get()
        );
    }

    public function applyToText(string $text, Collection $glossaries): array
    {
        $appliedGlossaries = [];

        foreach ($glossaries as $glossary) {
            $pattern = '/(?<![\pL\pN_])'.preg_quote($glossary->text, '/').'(?![\pL\pN_])/u';
            if (! $glossary->is_case_sensitive) {
                $pattern .= 'i';
            }

            $replacement = $glossary->rule === GlossaryRule::NEVER_TRANSLATE
                ? $glossary->text
                : (string) $glossary->translated;

            $replaced = preg_replace_callback($pattern, function () use ($glossary, $replacement, &$appliedGlossaries) {
                $appliedGlossaries[$glossary->placeholder] = $replacement;

                return $glossary->placeholder;
            }, $text);

            if ($replaced !== null) {
                $text = $replaced;
            }
        }

        return [
            'text' => $text,
            'applied_glossaries' => $appliedGlossaries,
        ];
    }

    public function replacePlaceholders(string $text, array $appliedGlossaries): string
    {
        foreach ($appliedGlossaries as $placeholder => $replacement) {
            $text = str_replace($placeholder, $replacement, $text);
        }

        return $text;
    }

    public static function registerCacheKey(int $projectId, string $langCode): void
    {
        $registryKey = self::getRegistryKey($projectId);
        $cacheKey = self::getCacheKey($projectId, $langCode);

        $keys = collect(Cache::get($registryKey, []))
            ->push($cacheKey)
            ->unique()
            ->values()
            ->all();

        Cache::forever($registryKey, $keys);
    }

    public static function invalidateCache(Project $project): void
    {
        self::clearCacheByProjectId($project->id, $project->languages()
            ->pluck('languages.iso_2')
            ->push($project->originalLanguage?->iso_2)
            ->filter()
            ->all());
    }

    public static function clearCacheByProjectId(int $projectId, array $currentLanguageCodes = []): void
    {
        $registryKey = self::getRegistryKey($projectId);

        $knownKeys = collect(Cache::get($registryKey, []));
        $currentLanguageKeys = collect($currentLanguageCodes)
            ->filter()
            ->map(fn ($langCode) => self::getCacheKey($projectId, $langCode));

        $keysToForget = $knownKeys
            ->merge($currentLanguageKeys)
            ->unique()
            ->values();

        foreach ($keysToForget as $cacheKey) {
            Cache::forget($cacheKey);
        }

        Cache::forget($registryKey);
    }

    public static function invalidateCacheForGlossary(Glossary $glossary): void
    {
        self::invalidateCache($glossary->project);
    }

    public static function clearAllGlossaryCache(): int
    {
        $count = 0;
        $projects = Project::all();

        foreach ($projects as $project) {
            self::invalidateCache($project);
            $count++;
        }

        return $count;
    }
}
