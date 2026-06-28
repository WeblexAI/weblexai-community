<?php

namespace App\Models;

use App\Enums\ModelStatus;
use App\Enums\TranslationQuality;
use App\Enums\TranslationType;
use App\Observers\TranslationObserver;
use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property string $page_id
 * @property string $project_id
 * @property Page $page
 * @property string $text
 * @property string $text_hash
 * @property string $translated
 * @property TranslationQuality $quality
 * @property string $source_lang_id
 * @property string $source_language
 * @property string $target_lang_id
 * @property string $target_language
 * @property ModelStatus $is_active
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property int $created_by_id
 * @property User $creator
 * @property TranslationType $type
 * @property string $node
 * @property string $attr
 * @property bool $is_original
 * @property bool $is_reviewed
 * @property bool $is_on
 * @property int $total_words
 * @property Carbon|null $last_used_at
 */
#[ObservedBy(TranslationObserver::class)]
class Translation extends Model
{
    use BaseModelTrait, HasFactory, LogsActivity;

    protected $casts = [
        'type' => TranslationType::class,
        'quality' => TranslationQuality::class,
        'is_on' => 'boolean',
        'is_reviewed' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    protected static $recordEvents = ['updated'];

    public function getActivitylogOptions(): LogOptions
    {
        $actorName = auth()->user()?->name
            ?? $this->creator?->name
            ?? $this->project?->user?->name
            ?? 'System';

        return LogOptions::defaults()
            ->setDescriptionForEvent(function ($event) use ($actorName) {
                return "{$actorName} has {$event} translation";
            });
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function sourceLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'source_lang_id');
    }

    public function targetLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'target_lang_id');
    }

    #[Scope]
    protected function whereTextContains(Builder $query, string $text, bool $isCaseSensitive = false): void
    {
        if ($isCaseSensitive) {
            $query->whereRaw('text LIKE ?', ["%{$text}%"]);
        } else {
            $query->whereRaw('text ILIKE ?', ["%{$text}%"]);
        }
    }

    #[Scope]
    protected function whereTranslatedContains(Builder $query, string $text, bool $isCaseSensitive = false): void
    {
        if ($isCaseSensitive) {
            $query->whereRaw('translated LIKE ?', ["%{$text}%"]);
        } else {
            $query->whereRaw('translated ILIKE ?', ["%{$text}%"]);
        }
    }
}
