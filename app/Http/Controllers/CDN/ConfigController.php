<?php

namespace App\Http\Controllers\CDN;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Project;
use App\Services\Cache\ConfigCacheStore;
use App\Support\UrlHelper;
use Illuminate\Http\JsonResponse;

class ConfigController extends Controller
{
    public function __construct(private readonly ConfigCacheStore $configCache) {}

    public function __invoke(): JsonResponse
    {
        $project = request()->attributes->get('project');
        [, $pageDomain] = app(UrlHelper::class)->getDomainAndOrigin(
            request()->attributes->get('pageUrl'),
        );
        $config = $this->configCache->get($project->id, $pageDomain)
            ?? $this->buildConfig($project, $pageDomain);

        if ($config['is_active']) {
            $this->configCache->set($project->id, $pageDomain, $config);
        }

        return $this->jsonSuccess($config);
    }

    private function buildConfig(Project $project, string $pageDomain): array
    {
        $page = $project->pages()->where('domain', $pageDomain)->first();
        $publicLanguages = $project->languages()
            ->wherePivot('is_public', true)
            ->wherePivot('is_disabled', false)
            ->get();

        if ($publicLanguages->isEmpty() || ($page && (! $page->is_active || $page->is_blacklisted))) {
            return ['is_active' => false];
        }

        $languageData = fn (Language $language): array => [
            'id' => $language->id,
            'name' => $language->name,
            'iso_2' => $language->iso_2,
            'flag' => $language->getFirstMediaUrl('flag') ?: null,
        ];
        $switcher = $project->languageSwitcherConfig;

        return [
            'original_language' => $languageData($project->originalLanguage),
            'languages' => [
                ...$publicLanguages->map($languageData)->values()->all(),
                $languageData($project->originalLanguage),
            ],
            'switcher_config' => [
                'target_parent_selector' => $switcher?->target_parent_selector,
                'should_display_name' => $switcher?->should_display_name ?? true,
                'should_display_full_name' => $switcher?->should_display_full_name ?? true,
                'should_display_flag' => $switcher?->should_display_flag ?? true,
                'size' => $switcher?->size ?? 100,
                'should_open_on_hover' => $switcher?->should_open_on_hover ?? false,
                'should_close_on_outside_click' => $switcher?->should_close_on_outside_click ?? true,
                'should_show_by_device' => $switcher?->should_show_by_device ?? false,
                'preferred_device' => $switcher?->preferred_device?->value,
                'device_pixel_breakpoint' => $switcher?->device_pixel_breakpoint ?? 768,
            ],
            'is_active' => true,
            'page' => $pageDomain,
            'excluded_blocks' => $project->excludedBlocks->pluck('selector')->all(),
            'hide_water_mark' => false,
        ];
    }
}
