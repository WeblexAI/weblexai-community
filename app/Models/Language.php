<?php

namespace App\Models;

use App\Enums\ModelStatus;
use App\Observers\LanguageObserver;
use App\Pivots\ProjectLanguagePivot;
use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $country_name
 * @property string $iso_2
 * @property string $iso_3
 * @property string $color
 * @property ModelStatus $is_active
 * @property string $created_at
 * @property string $updated_at
 * @property Collection<Project> $projects
 * @property Collection<TranslationRequest> $sourceTranslationRequests
 * @property Collection<TranslationRequest> $targetTranslationRequests
 * @property Translation $translations
 * @property mixed|Media|null $flag
 */
#[ObservedBy(LanguageObserver::class)]
class Language extends Model implements HasMedia
{
    use BaseModelTrait, HasFactory, InteractsWithMedia;

    protected $appends = [
        'flag_url',
    ];

    protected $casts = [
        'is_active' => ModelStatus::class,
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('flag')
            ->singleFile()
            ->useFallbackUrl('');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_language')
            ->using(ProjectLanguagePivot::class)
            ->withPivot(['is_public', 'should_display_automatics']);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(Translation::class, 'target_lang_id');
    }

    public function glossaries(): BelongsToMany
    {
        return $this->belongsToMany(Glossary::class, 'glossary_language');
    }

    protected function flagUrl(): Attribute
    {
        return Attribute::get(fn () => $this->getFirstMediaUrl('flag'));
    }

    public function sourceTranslationRequests(): HasMany
    {
        return $this->hasMany(TranslationRequest::class, 'source_lang_id');
    }

    public function targetTranslationRequests(): HasMany
    {
        return $this->hasMany(TranslationRequest::class, 'target_lang_id');
    }
}
