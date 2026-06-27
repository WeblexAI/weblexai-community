<?php

namespace App\Imports;

use App\Enums\TranslationQuality;
use App\Enums\TranslationType;
use App\Models\Language;
use App\Models\Page;
use App\Models\Translation;
use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;

class TranslationImport implements ToModel, WithBatchInserts, WithChunkReading, WithHeadingRow, WithUpserts
{
    public function __construct(
        private readonly User $user,
        private readonly Page $page,
        private readonly Language $sourceLanguage,
        private readonly Language $targetLanguage,
    ) {}

    public function model(array $row): Translation
    {
        return new Translation([
            'project_id' => $this->page->project_id,
            'page_id' => $this->page->id,
            'source_lang_id' => $this->sourceLanguage->id,
            'target_lang_id' => $this->targetLanguage->id,
            'created_by_id' => $this->user->id,
            'text' => $row['text'],
            'translated' => $row['translated'],
            'type' => TranslationType::tryFrom(strtolower($row['type'] ?? '')) ?? TranslationType::INNER_TEXT,
            'is_on' => strtolower($row['status'] ?? '') === 'active',
            'is_reviewed' => strtolower($row['is_reviewed'] ?? '') === 'yes',
            'quality' => TranslationQuality::tryFrom(strtolower($row['quality'] ?? ''))
                ?? TranslationQuality::MANUAL,
            'total_words' => str_word_count($row['text']),
        ]);
    }

    public function uniqueBy(): array
    {
        return ['project_id', 'page_id', 'target_lang_id', 'text_hash'];
    }

    public function batchSize(): int
    {
        return 500;
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
