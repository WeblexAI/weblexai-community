<?php

namespace App\Exports;

use App\Models\Language;
use App\Models\Page;
use App\Models\Project;
use App\Models\Translation;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TranslationExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        private readonly Project $project,
        private readonly Page $page,
        private readonly Language $targetLanguage,
    ) {}

    public function query()
    {
        return Translation::query()
            ->where('project_id', $this->project->id)
            ->where('page_id', $this->page->id)
            ->where('target_lang_id', $this->targetLanguage->id);
    }

    public function map($translation): array
    {
        return [
            $translation->text,
            $translation->translated,
            $translation->type?->value ?? '',
            $translation->is_on ? 'active' : 'inactive',
            $translation->is_reviewed ? 'yes' : 'no',
            $translation->quality->value,
            $translation->created_at->format('Y-m-d H:i:s'),
        ];
    }

    public function headings(): array
    {
        return [
            ["Translations to {$this->targetLanguage->name} for {$this->page->origin}"],
            ['text', 'translated', 'type', 'status', 'is_reviewed', 'quality', 'date'],
        ];
    }
}
