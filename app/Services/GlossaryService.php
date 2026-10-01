<?php

namespace App\Services;

use App\Enums\GlossaryRule;
use App\Enums\ModelStatus;
use App\Models\Glossary;
use App\Models\Language;
use App\Models\Project;
use App\Settings\CacheSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GlossaryService
{
    public static function store(Project $project, array $data): Glossary
    {
        return DB::transaction(function () use ($project, $data): Glossary {
            Project::query()->whereKey($project->id)->lockForUpdate()->firstOrFail();
            $languages = $data['languages'] ?? [];
            $rule = GlossaryRule::from($data['rule']);
            $glossary = Glossary::withoutEvents(fn () => $project->glossaries()->create([
                'text' => $data['text'],
                'translated' => $rule === GlossaryRule::ALWAYS_TRANSLATE ? $data['translated'] : $data['text'],
                'is_case_sensitive' => $data['is_case_sensitive'],
                'is_all_languages' => $languages === [],
                'rule' => $rule,
                'placeholder' => 'GLS'.Str::ulid(),
                'uuid' => Str::uuid()->toString(),
                'created_by_id' => auth()->id(),
            ]));
            $glossary->languages()->sync($languages);
            self::invalidateCacheForGlossary($glossary);

            return $glossary;
        });
    }

    public static function update(Project $project, Glossary $glossary, array $data): Glossary
    {
        abort_unless($glossary->project_id === $project->id, 404);

        return DB::transaction(function () use ($glossary, $data): Glossary {
            Project::query()->whereKey($glossary->project_id)->lockForUpdate()->firstOrFail();
            self::markAffected($glossary);
            $languages = $data['languages'] ?? [];
            $rule = GlossaryRule::from($data['rule']);
            Glossary::withoutEvents(fn () => $glossary->update([
                'translated' => $rule === GlossaryRule::ALWAYS_TRANSLATE ? $data['translated'] : $glossary->text,
                'is_case_sensitive' => $data['is_case_sensitive'],
                'is_all_languages' => $languages === [],
                'rule' => $rule,
            ]));
            $glossary->languages()->sync($languages);
            $glossary->unsetRelation('languages');
            self::invalidateCacheForGlossary($glossary);

            return $glossary;
        });
    }

    public static function delete(Project $project, Glossary $glossary): void
    {
        abort_unless($glossary->project_id === $project->id, 404);
        DB::transaction(function () use ($glossary): void {
            Project::query()->whereKey($glossary->project_id)->lockForUpdate()->firstOrFail();
            self::invalidateCacheForGlossary($glossary);
            $glossary->languages()->detach();
            Glossary::withoutEvents(fn () => $glossary->delete());
        });
    }

    public static function deleteBulk(Project $project, array $ids): void
    {
        DB::transaction(function () use ($project, $ids): void {
            foreach ($project->glossaries()->whereIn('id', $ids)->get() as $glossary) {
                self::delete($project, $glossary);
            }
        });
    }

    public static function markAffected(Glossary $glossary, ?array $previous = null): void
    {
        $text = $previous['text'] ?? $glossary->text;
        $caseSensitive = $previous['is_case_sensitive'] ?? $glossary->is_case_sensitive;
        $allLanguages = $previous['is_all_languages'] ?? $glossary->is_all_languages;
        $languageIds = $glossary->languages()->pluck('languages.id')->all();
        $pattern = '/(?<![\pL\pN_])'.preg_quote($text, '/').'(?![\pL\pN_])/u'.($caseSensitive ? '' : 'i');
        $glossary->project->translations()
            ->when(! $allLanguages, fn ($query) => $query->whereIn('target_lang_id', $languageIds))
            ->select(['id', 'text'])->chunkById(500, function ($translations) use ($pattern): void {
                $ids = $translations->filter(fn ($translation): bool => preg_match($pattern, $translation->text) === 1)->pluck('id');
                DB::table('translations')->whereIn('id', $ids)->update(['needs_regeneration' => true]);
            });
    }

    public static function getCacheKey(int $projectId, string $langCode): string
    {
        $revision = DB::table('projects')->where('id', $projectId)->value('generation_revision') ?? 0;

        return "glossary:{$projectId}:{$revision}:{$langCode}";
    }

    public function getProjectGlossaries(Project $project, Language $language): Collection
    {
        $load = fn () => $project->glossaries()
            ->where('is_active', ModelStatus::ACTIVE)
            ->where(fn ($query) => $query->where('is_all_languages', true)
                ->orWhereHas('languages', fn ($languages) => $languages->where('languages.id', $language->id)))
            ->orderByRaw('LENGTH("text") DESC')->get();
        try {
            return Cache::remember(self::getCacheKey($project->id, $language->iso_2), app(CacheSettings::class)->getGlossaryTtlInSeconds(), $load);
        } catch (\Throwable $exception) {
            report($exception);

            return $load();
        }
    }

    public function applyToText(string $text, Collection $glossaries): array
    {
        $applied = [];
        foreach ($glossaries as $glossary) {
            $pattern = '/(?<![\pL\pN_])'.preg_quote($glossary->text, '/').'(?![\pL\pN_])/u'.($glossary->is_case_sensitive ? '' : 'i');
            $replacement = $glossary->rule === GlossaryRule::NEVER_TRANSLATE ? $glossary->text : (string) $glossary->translated;
            $text = preg_replace_callback($pattern, function () use ($glossary, $replacement, &$applied): string {
                $applied[$glossary->placeholder] = $replacement;

                return $glossary->placeholder;
            }, $text) ?? $text;
        }

        return ['text' => $text, 'applied_glossaries' => $applied];
    }

    public function replacePlaceholders(string $text, array $appliedGlossaries): string
    {
        foreach ($appliedGlossaries as $placeholder => $replacement) {
            if (! str_contains($text, $placeholder)) {
                throw new \RuntimeException('The translation provider lost a glossary placeholder.');
            }
            $text = str_replace($placeholder, $replacement, $text);
        }

        return $text;
    }

    public static function invalidateCache(Project $project): void
    {
        app(ProjectRevisionService::class)->bumpGeneration($project->id);
    }

    public static function clearCacheByProjectId(int $projectId, array $currentLanguageCodes = []): void
    {
        app(ProjectRevisionService::class)->bumpGeneration($projectId);
    }

    public static function invalidateCacheForGlossary(Glossary $glossary): void
    {
        self::markAffected($glossary);
        self::invalidateCache($glossary->project);
    }

    public static function clearAllGlossaryCache(): int
    {
        $ids = Project::query()->pluck('id');
        foreach ($ids as $id) {
            app(ProjectRevisionService::class)->bumpGeneration($id);
        }

        return $ids->count();
    }
}
