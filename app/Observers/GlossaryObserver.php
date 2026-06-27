<?php

namespace App\Observers;

use App\Models\Glossary;
use App\Services\GlossaryService;
use Illuminate\Support\Str;

class GlossaryObserver
{
    public function creating(Glossary $glossary): void
    {
        if (empty($glossary->placeholder)) {
            $glossary->placeholder = 'GLS'.Str::ulid()->toString();
        }
    }

    public function created(Glossary $glossary): void
    {
        GlossaryService::invalidateCacheForGlossary($glossary);
    }

    public function updated(Glossary $glossary): void
    {
        GlossaryService::invalidateCacheForGlossary($glossary);
    }

    public function deleted(Glossary $glossary): void
    {
        GlossaryService::invalidateCacheForGlossary($glossary);
    }

    public function restored(Glossary $glossary): void
    {
        GlossaryService::invalidateCacheForGlossary($glossary);
    }

    public function forceDeleted(Glossary $glossary): void
    {
        GlossaryService::invalidateCacheForGlossary($glossary);
    }
}
