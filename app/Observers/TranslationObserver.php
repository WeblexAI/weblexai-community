<?php

namespace App\Observers;

use App\Enums\TranslationQuality;
use App\Models\Translation;
use App\Services\ProjectRevisionService;
use App\Support\TextHasher;

class TranslationObserver
{
    public function __construct(private readonly ProjectRevisionService $revisions) {}

    public function creating(Translation $translation): void
    {
        $translation->text_hash = TextHasher::hash($translation->text);
        $translation->source_context ??= '';
        $translation->context_hash = $translation->source_context === '' ? '' : TextHasher::hash($translation->source_context);
        $translation->attr ??= '';
        $translation->type ??= 'text';
    }

    public function updating(Translation $translation): void
    {
        if ($translation->isDirty('text')) {
            $translation->text_hash = TextHasher::hash($translation->text);
        }
        if ($translation->isDirty('source_context')) {
            $translation->context_hash = $translation->source_context === '' ? '' : TextHasher::hash($translation->source_context);
        }
        if ($translation->isDirty('translated')) {
            $translation->quality = TranslationQuality::MANUAL;
            $translation->is_reviewed = false;
            $translation->needs_regeneration = false;
        }
    }

    public function updated(Translation $translation): void
    {
        $this->revisions->bumpDelivery($translation->project_id);
    }

    public function deleted(Translation $translation): void
    {
        $this->revisions->bumpDelivery($translation->project_id);
    }
}
