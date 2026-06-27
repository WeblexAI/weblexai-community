<?php

namespace App\DTOs\CDN;

use App\Enums\TranslationModelType;
use App\Models\Language;
use App\Models\Page;
use App\Models\Project;
use App\Pivots\ProjectLanguagePivot;
use App\Support\TextHasher;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TranslationContext
{
    public Carbon $usageTrackedAt;

    public array $validated;

    public ?Project $project;

    public ?Page $page;

    public Collection $translatedItems;

    public Collection $cacheHits;

    public Collection $needsDbLookup;

    public Collection $dbHits;

    public Collection $needsNmtTranslation;

    public Collection $nmtTranslated;

    public Collection $needsCaching;

    public Collection $translationIdsToTouch;

    public string $source;

    public string $target;

    public Language|Model|null $sourceLanguage;

    public Language|Model|null $targetLanguage;

    public ?ProjectLanguagePivot $targetLanguagePivot;

    public TranslationModelType $useModel;

    public array $llmOptions = [];

    public array $appliedGlossaries = [];

    public ?Closure $streamCallback = null;

    private array $hashCache = [];

    public ?string $stoppageClass = null;

    public const LAST_USED_REFRESH_AFTER_DAYS = 5;

    public function __construct(array $validated, Project $project)
    {
        $this->init($validated, $project);
    }

    private function init(array $validated, Project $project): void
    {
        $this->validated = $validated;
        $this->project = $project;
        $this->usageTrackedAt = now();

        $this->translatedItems = collect();
        $this->cacheHits = collect();
        $this->needsDbLookup = collect();
        $this->dbHits = collect();
        $this->needsNmtTranslation = collect();
        $this->nmtTranslated = collect();
        $this->needsCaching = collect();
        $this->translationIdsToTouch = collect();

        $this->source = $validated['source'];
        $this->target = $validated['target'];
        $this->sourceLanguage = $project->originalLanguage;
        $this->targetLanguage = null;
        $this->page = null;
        $this->targetLanguagePivot = null;

        $this->hashCache = [];
    }

    public function reset(): self
    {
        $this->init($this->validated, $this->project);

        return $this;
    }

    public function getTextHash(string $text): string
    {
        return $this->hashCache[$text] ??= TextHasher::hash($text);
    }

    public function setStreamCallback(Closure $callback): void
    {
        $this->streamCallback = $callback;
    }

    public function stream(string $source, Collection $items): void
    {
        if ($this->streamCallback && $items->isNotEmpty()) {
            ($this->streamCallback)($source, $items);
        }
    }

    public function markTranslationAsUsedIfStale(?int $translationId, Carbon|string|null $lastUsedAt = null): bool
    {
        if (! $translationId) {
            return false;
        }

        if ($lastUsedAt === null) {
            $this->translationIdsToTouch->push($translationId);

            return true;
        }

        try {
            $resolvedLastUsedAt = $lastUsedAt instanceof Carbon ? $lastUsedAt : Carbon::parse($lastUsedAt);
        } catch (\Throwable) {
            $this->translationIdsToTouch->push($translationId);

            return true;
        }

        $staleBefore = $this->usageTrackedAt->copy()->subDays(self::LAST_USED_REFRESH_AFTER_DAYS);

        if ($resolvedLastUsedAt->lte($staleBefore)) {
            $this->translationIdsToTouch->push($translationId);

            return true;
        }

        return false;
    }

    public function usageTrackedAtIsoString(): string
    {
        return $this->usageTrackedAt->toISOString();
    }
}
