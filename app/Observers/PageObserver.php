<?php

namespace App\Observers;

use App\Models\Page;
use App\Services\ProjectRevisionService;

class PageObserver
{
    public function updated(Page $page): void
    {
        if ($page->wasChanged(['is_active', 'is_blacklisted', 'domain', 'origin'])) {
            app(ProjectRevisionService::class)->bumpDelivery($page->project_id);
        }
    }

    public function deleted(Page $page): void
    {
        app(ProjectRevisionService::class)->bumpDelivery($page->project_id);
    }
}
