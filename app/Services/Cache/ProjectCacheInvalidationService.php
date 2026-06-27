<?php

namespace App\Services\Cache;

use App\Services\GlossaryService;

class ProjectCacheInvalidationService
{
    public function __construct(
        private readonly ConfigCacheInvalidationService $configCacheInvalidation,
        private readonly TranslationCacheInvalidationService $translationCacheInvalidation,
    ) {}

    public function clearProject(
        int $projectId,
        bool $config = true,
        bool $translations = false,
    ): void {
        if ($config) {
            $this->clearProjectConfig($projectId);
        }

        if ($translations) {
            $this->clearProjectTranslations($projectId);
        }
    }

    public function clearProjectConfig(int $projectId): void
    {
        $this->configCacheInvalidation->clearProject($projectId);
    }

    public function clearProjectTranslations(int $projectId): void
    {
        $this->translationCacheInvalidation->forgetProject($projectId);
    }

    public function clearDeletedProject(int $projectId): void
    {
        $this->clearProject($projectId, config: true, translations: true);
        GlossaryService::clearCacheByProjectId($projectId);
    }
}
