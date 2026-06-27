<?php

namespace App\Models;

use App\Enums\ModelStatus;
use App\Traits\BaseModelTrait;
use CyrildeWit\EloquentViewable\Contracts\Viewable;
use CyrildeWit\EloquentViewable\InteractsWithViews;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $project_id
 * @property string $title
 * @property string $domain
 * @property string $origin
 * @property ModelStatus $is_active
 * @property string $created_at
 * @property string $updated_at
 * @property Project $project
 * @property Collection<Translation> $translations
 * @property int $created_by_id
 * @property User $creator
 * @property string $path
 * @property bool $is_blacklisted
 * @property array $blacklisted_languages
 * @property Collection<TranslationRequest> $translationRequests
 */
class Page extends Model implements Viewable
{
    use BaseModelTrait, HasFactory, InteractsWithViews, LogsActivity;

    protected $appends = ['path'];

    protected $casts = [
        'blacklisted_languages' => 'array',
    ];

    protected static $recordEvents = ['updated', 'deleted'];

    protected $removeViewsOnDelete = true;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->setDescriptionForEvent(function ($event) {
                return auth()->user()->name." has {$event} page {$this->origin}";
            });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(Translation::class);
    }

    public function translationRequests(): HasMany
    {
        return $this->hasMany(TranslationRequest::class);
    }

    public function originalTranslations(): Builder
    {
        return Translation::query()
            ->where('page_id', $this->id)
            ->where('source_lang_id', $this->project->original_language_id)
            ->where('target_lang_id', $this->project->original_language_id);
    }

    public function notOriginalTranslations(): Builder
    {
        return Translation::query()
            ->where('page_id', $this->id)
            ->where('source_lang_id', $this->project->original_language_id)
            ->whereNot('target_lang_id', $this->project->original_language_id);
    }

    protected function path(): Attribute
    {
        return Attribute::make(
            get: fn () => parse_url($this->origin, PHP_URL_PATH) ?? '/'
        );
    }
}
