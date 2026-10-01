<?php

namespace App\Imports;

use App\Enums\TranslationQuality;
use App\Enums\TranslationType;
use App\Models\Language;
use App\Models\Page;
use App\Models\Translation;
use App\Models\User;
use App\Support\TextHasher;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpsertColumns;
use Maatwebsite\Excel\Concerns\WithUpserts;

class TranslationImport implements ToModel, WithBatchInserts, WithChunkReading, WithHeadingRow, WithUpsertColumns, WithUpserts
{
    public function __construct(
        private readonly User $user,
        private readonly Page $page,
        private readonly Language $sourceLanguage,
        private readonly Language $targetLanguage,
    ) {}

    public function model(array $row): Translation
    {
        $type = TranslationType::tryFrom(strtolower($row['type'] ?? 'text'));
        $attr = (string) ($row['attr'] ?? '');
        if (! $type || ($type === TranslationType::ATTRIBUTE && ! in_array($attr, ['placeholder', 'alt', 'title', 'aria-label', 'aria-description'], true))
            || ($type === TranslationType::INNER_TEXT && $attr !== '')) {
            throw new \InvalidArgumentException('Invalid translation type or attribute.');
        }
        $context = (string) ($row['context'] ?? '');

        return new Translation([
            'uuid' => Str::uuid()->toString(),
            'project_id' => $this->page->project_id,
            'page_id' => $this->page->id,
            'source_lang_id' => $this->sourceLanguage->id,
            'target_lang_id' => $this->targetLanguage->id,
            'created_by_id' => $this->user->id,
            'text' => $row['text'],
            'text_hash' => TextHasher::hash($row['text']),
            'source_context' => $context,
            'context_hash' => $context === '' ? '' : TextHasher::hash($context),
            'translated' => $row['translated'],
            'type' => $type,
            'attr' => $attr,
            'needs_regeneration' => false,
            'is_on' => strtolower($row['status'] ?? '') === 'active',
            'is_reviewed' => strtolower($row['is_reviewed'] ?? '') === 'yes',
            'quality' => TranslationQuality::tryFrom(strtolower($row['quality'] ?? ''))
                ?? TranslationQuality::MANUAL,
            'total_words' => str_word_count($row['text']),
        ]);
    }

    public function uniqueBy(): array
    {
        return ['project_id', 'page_id', 'target_lang_id', 'type', 'attr', 'text_hash', 'context_hash'];
    }

    public function batchSize(): int
    {
        return 500;
    }

    public function upsertColumns(): array
    {
        return ['translated', 'is_on', 'is_reviewed', 'quality', 'needs_regeneration', 'created_by_id', 'updated_at'];
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
