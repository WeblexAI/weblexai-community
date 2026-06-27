<?php

namespace App\Jobs\CDN;

use App\Enums\TranslationType;
use App\Models\Page;
use App\Models\Project;
use App\Models\Translation;
use App\Services\Cache\TranslationCacheStore;
use App\Support\TextHasher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class StoreTranslationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected array $translations,
        protected int $pageId,
        protected int $projectId,
    ) {}

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("store-translations:page:{$this->pageId}"))->releaseAfter(30),
        ];
    }

    public function handle(TranslationCacheStore $cache): void
    {
        $usedAt = now();
        $page = Page::find($this->pageId);
        $project = Project::find($this->projectId);

        if (! $page || ! $project || $page->project_id !== $project->id || $this->translations === []) {
            return;
        }

        $rows = collect($this->translations)->map(fn (array $translation): array => [
            'page_id' => $page->id,
            'project_id' => $project->id,
            'text' => $translation['text'],
            'total_words' => str_word_count($translation['text']),
            'translated' => $translation['translated'],
            'type' => TranslationType::INNER_TEXT,
            'attr' => '',
            'source_lang_id' => $translation['source_lang_id'],
            'target_lang_id' => $translation['target_lang_id'],
            'uuid' => Str::uuid()->toString(),
            'text_hash' => TextHasher::hash($translation['text']),
            'last_used_at' => $usedAt,
        ]);

        Translation::query()->fillAndInsertOrIgnore($rows->all());

        $targetLanguageId = $this->translations[0]['target_lang_id'] ?? null;
        $targetLanguageCode = $this->translations[0]['target'] ?? null;

        if (! $targetLanguageId || ! $targetLanguageCode) {
            return;
        }

        $stored = Translation::query()
            ->where('project_id', $project->id)
            ->where('page_id', $page->id)
            ->where('target_lang_id', $targetLanguageId)
            ->whereIn('text_hash', $rows->pluck('text_hash'))
            ->get(['id', 'text_hash', 'translated'])
            ->keyBy('text_hash');

        Translation::withoutTimestamps(fn () => Translation::query()
            ->whereIn('id', $stored->pluck('id'))
            ->update(['last_used_at' => $usedAt]));

        $payload = $rows->mapWithKeys(function (array $row) use ($stored, $usedAt): array {
            $translation = $stored->get($row['text_hash']);

            return $translation ? [
                $row['text_hash'] => [
                    'translated' => $translation->translated,
                    'translation_id' => $translation->id,
                    'last_used_at' => $usedAt->toISOString(),
                ],
            ] : [];
        })->all();

        $cache->setMany($project->id, $page->id, $targetLanguageCode, $payload);
    }
}
