<?php

namespace App\Services;

use App\Models\Language;
use App\Models\Project;
use App\Services\Cache\ProjectCacheInvalidationService;

class ProjectService
{
    public static function update(Project $project, array $data): Project
    {
        $project->update([
            'name' => $data['name'],
            'should_display_automatics' => $data['should_display_automatics'],
        ]);

        app(ProjectCacheInvalidationService::class)->clearProjectConfig($project->id);

        return $project;
    }

    public static function attachLanguage(Project $project, array $data): Project
    {
        $language = Language::query()->find($data['language_id'], ['id', 'name']);
        if (! $language) {
            return $project;
        }

        $project->languages()->syncWithoutDetaching([
            $language->id => [
                'is_public' => $data['is_public'],
                'should_display_automatics' => $data['should_display_automatics'],
            ],
        ]);

        app(ProjectCacheInvalidationService::class)->clearProjectConfig($project->id);

        activity()
            ->event('created')
            ->log(auth()->user()->name." added language {$language->name} to project");

        return $project;
    }

    public static function detachLanguage(Project $project, Language $language): Project
    {
        $project->languages()->detach($language->id);
        $project->translations()->where('target_lang_id', $language->id)->delete();
        $project->translationRequests()->where('target_lang_id', $language->id)->delete();

        app(ProjectCacheInvalidationService::class)->clearProject(
            $project->id,
            config: true,
            translations: true,
        );

        activity()
            ->event('deleted')
            ->log(auth()->user()->name." removed language {$language->name} from project");

        return $project;
    }

    public static function updateLanguageSwitcher(Project $project, array $data): void
    {
        $switcher = $project->languageSwitcherConfig;
        $switcher->update([
            'target_parent_selector' => $data['target_parent_selector'],
            'should_display_name' => $data['should_display_name'],
            'should_display_full_name' => $data['should_display_full_name'],
            'should_display_flag' => $data['should_display_flag'],
            'size' => $data['size'],
            'should_open_on_hover' => $data['should_open_on_hover'],
            'should_close_on_outside_click' => $data['should_open_on_hover']
                ? $switcher->should_close_on_outside_click
                : $data['should_close_on_outside_click'],
            'should_show_by_device' => $data['should_show_by_device'],
            'preferred_device' => $data['should_show_by_device']
                ? $data['preferred_device']
                : $switcher->preferred_device,
            'device_pixel_breakpoint' => $data['should_show_by_device']
                ? $data['device_pixel_breakpoint']
                : $switcher->device_pixel_breakpoint,
        ]);
    }

    public static function updateLanguagePivot(Project $project, Language $language, array $attributes): void
    {
        $project->languages()->updateExistingPivot($language->id, $attributes);
        app(ProjectCacheInvalidationService::class)->clearProjectConfig($project->id);
    }

    public static function rotateApiKey(Project $project): string
    {
        $plainTextApiKey = bin2hex(random_bytes(32));
        $project->updateQuietly([
            'api_key' => $plainTextApiKey,
            'api_key_hash' => hash('sha256', $plainTextApiKey),
        ]);

        return $plainTextApiKey;
    }
}
