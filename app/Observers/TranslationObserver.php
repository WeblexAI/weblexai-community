<?php

namespace App\Observers;

use App\Enums\TranslationQuality;
use App\Models\Language;
use App\Models\Translation;
use App\Services\Cache\TranslationCacheInvalidationService;
use App\Services\Cache\TranslationCacheStore;
use App\Support\TextHasher;

class TranslationObserver
{
    protected array $previousCacheEntries = [];

    public function __construct(
        protected TranslationCacheStore $cacheStore,
        protected TranslationCacheInvalidationService $cacheInvalidation,
    ) {}

    public function updated(Translation $translation): void
    {
        $previousCacheEntry = $this->pullPreviousCacheEntry($translation);

        if ($previousCacheEntry !== null) {
            $this->cacheInvalidation->forget(
                $previousCacheEntry['project_id'],
                $previousCacheEntry['page_id'],
                $previousCacheEntry['lang_code'],
                $previousCacheEntry['text_hash'],
            );
        }

        if ($translation->wasChanged(['translated', 'text_hash', 'page_id', 'project_id', 'target_lang_id'])) {
            $this->cacheStore->set(
                $translation->project_id,
                $translation->page_id,
                $translation->targetLanguage->iso_2,
                $translation->text_hash,
                [
                    'translated' => $translation->translated,
                    'translation_id' => $translation->id,
                    'last_used_at' => $translation->last_used_at?->toISOString(),
                ]
            );
        }

    }

    public function updating(Translation $translation): void
    {
        $this->rememberPreviousCacheEntry($translation);

        if ($translation->isDirty('text')) {
            $translation->text_hash = TextHasher::hash($translation->text);
        }

        if ($translation->isDirty('translated') && $translation->quality == TranslationQuality::AUTOMATIC) {
            $translation->quality = TranslationQuality::MANUAL;
        }
    }

    public function deleted(Translation $translation): void
    {
        $this->cacheInvalidation->forget(
            $translation->project_id,
            $translation->page_id,
            $translation->targetLanguage->iso_2,
            $translation->text_hash
        );
    }

    public function forceDeleted(Translation $translation): void
    {
        $this->cacheInvalidation->forget(
            $translation->project_id,
            $translation->page_id,
            $translation->targetLanguage->iso_2,
            $translation->text_hash
        );
    }

    private function rememberPreviousCacheEntry(Translation $translation): void
    {
        $identity = spl_object_id($translation);

        $targetLanguage = Language::query()->find($translation->getOriginal('target_lang_id'), ['id', 'iso_2']);

        if (! $targetLanguage?->iso_2) {
            return;
        }

        $this->previousCacheEntries[$identity] = [
            'project_id' => (int) $translation->getOriginal('project_id'),
            'page_id' => (int) $translation->getOriginal('page_id'),
            'lang_code' => $targetLanguage->iso_2,
            'text_hash' => (string) $translation->getOriginal('text_hash'),
        ];
    }

    private function pullPreviousCacheEntry(Translation $translation): ?array
    {
        $identity = spl_object_id($translation);
        $entry = $this->previousCacheEntries[$identity] ?? null;

        unset($this->previousCacheEntries[$identity]);

        return $entry;
    }
}
