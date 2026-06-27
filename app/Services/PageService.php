<?php

namespace App\Services;

use App\Models\Language;
use App\Models\Page;
use App\Models\Project;
use App\Services\Cache\ConfigCacheInvalidationService;
use App\Services\Cache\TranslationCacheInvalidationService;

class PageService
{
    public static function toggleBlacklist(Page $page, $is_blacklisted = null): Page
    {
        if (is_null($is_blacklisted)) {
            $is_blacklisted = ! $page->is_blacklisted;
        }
        $page->notOriginalTranslations()->delete();

        app(TranslationCacheInvalidationService::class)->forgetPage($page->project_id, $page->id);
        app(ConfigCacheInvalidationService::class)->clearPage($page->project_id, $page->domain);

        $page->update([
            'is_blacklisted' => $is_blacklisted,
        ]);

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
