<?php

namespace App\Services\Cache;

use App\Services\ProjectRevisionService;

class TranslationCacheInvalidationService
{
    public function __construct(private readonly ProjectRevisionService $revisions) {}

    public function forget(int $projectId, int $pageId, string $langCode, string $textHash): void
    {
        $this->revisions->bumpDelivery($projectId);
    }

    public function forgetPageLang(int $projectId, int $pageId, string $langCode): void
    {
        $this->revisions->bumpDelivery($projectId);
    }

    public function forgetPage(int $projectId, int $pageId): void
    {
        $this->revisions->bumpDelivery($projectId);
    }

    public function forgetProject(int $projectId): void
    {
        $this->revisions->bumpDelivery($projectId);
    }

    public function forgetMany(
        int $projectId,
        int $pageId,
        string $langCode,
        array $textHashes,
    ): void {
        if ($textHashes !== []) {
            $this->revisions->bumpDelivery($projectId);
        }
    }
}
