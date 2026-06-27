<?php

namespace App\Models;

use App\Enums\ModelStatus;
use App\Enums\TranslationAudience;
use App\Enums\TranslationTone;
use App\Observers\ProjectObserver;
use App\Pivots\CollaboratorProjectPivot;
use App\Pivots\ProjectLanguagePivot;
use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $slug
 * @property int $user_id
 * @property int|null $provider_credential_id
 * @property int|null $original_language_id
 * @property string|null $api_key
 * @property string|null $api_key_hash
 * @property ModelStatus $is_active
 * @property bool $should_display_automatics
 * @property TranslationTone $translation_tone
 * @property TranslationAudience $translation_audience
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property-read bool $is_integrated
 */

#[ObservedBy(ProjectObserver::class)]
class Project extends Model
{
    use BaseModelTrait;
    use HasSlug;
    use LogsActivity;

    protected $with = ['originalLanguage'];

    protected $appends = ['is_integrated'];

    protected $hidden = [
        'api_key',
        'api_key_hash',
    ];

    protected $casts = [
        'api_key' => 'encrypted',
        'is_active' => ModelStatus::class,
        'should_display_automatics' => 'boolean',
        'translation_tone' => TranslationTone::class,
        'translation_audience' => TranslationAudience::class,
    ];

    protected static $recordEvents = ['created', 'updated'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->setDescriptionForEvent(fn (string $event) => sprintf(
                '%s %s project',
                auth()->user()?->name ?? 'System',
                $event,
            ));
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->project_id = $this->id;
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function providerCredential(): BelongsTo
    {
        return $this->belongsTo(ProviderCredential::class);
    }

    public function user(): BelongsTo
    {
        return $this->owner();
    }

    public function collaborators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user')
            ->using(CollaboratorProjectPivot::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function acceptedOrigins(): HasMany
    {
        return $this->hasMany(ProjectAcceptedOrigin::class);
    }

    public function translationRequests(): HasMany
    {
        return $this->hasMany(TranslationRequest::class);
    }

    public function excludedBlocks(): HasMany
    {
        return $this->hasMany(ExcludedBlock::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(Translation::class);
    }

    public function glossaries(): HasMany
    {
        return $this->hasMany(Glossary::class);
    }

    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class, 'project_language')
            ->using(ProjectLanguagePivot::class)
            ->withPivot(['is_public', 'should_display_automatics', 'is_disabled']);
    }

    public function languageSwitcherConfig(): HasOne
    {
        return $this->hasOne(LanguageSwitcherConfig::class);
    }

    public function originalLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'original_language_id');
    }

    public function originalTranslations(): Builder
    {
        return Translation::query()
            ->where('source_lang_id', $this->original_language_id)
            ->where('target_lang_id', $this->original_language_id)
            ->where('project_id', $this->id);
    }

    protected function isIntegrated(): Attribute
    {
        return Attribute::get(
            fn () => $this->acceptedOrigins()->exists()
                && $this->translations()->exists()
                && $this->languages()->exists()
        );
    }

    public function indexPage(): ?Page
    {
        return $this->pages()->latest()->first(['id', 'domain']);
    }
}
