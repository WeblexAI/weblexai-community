<?php

namespace App\Services;

use App\Models\Language;
use App\Models\Page;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class PageService
{
    public static function toggleBlacklist(Page $page, $is_blacklisted = null): Page
    {
        if (is_null($is_blacklisted)) {
            $is_blacklisted = ! $page->is_blacklisted;
        }
        DB::transaction(function () use ($page, $is_blacklisted): void {
            Project::query()->whereKey($page->project_id)->lockForUpdate()->firstOrFail();
            $page->update(['is_blacklisted' => $is_blacklisted]);
        });

        return $page;
    }

    public static function toggleBulkBlacklist(Project $project, array $data): void
    {
        $pages = $project->pages()
            ->whereIn('id', $data['page_ids'])
            ->get();

        foreach ($pages as $page) {
            self::toggleBlacklist($page, $data['is_blacklisted']);
        }
    }

    public static function retranslate(Page $page, Language $language): Page
    {

        return $page;
    }

    public static function bulkRetranslate(Language $language): void {}
}
