<?php

namespace App\Models;

use App\Enums\TranslationProvider;
use App\Observers\ProviderCredentialObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int|null $project_id
 * @property TranslationProvider $provider
 * @property string $api_key
 * @property string $service_account
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property-read string $provider_label
 * @property-read string $provider_type
 */

#[ObservedBy(ProviderCredentialObserver::class)]
class ProviderCredential extends Model
{
    protected $guarded = ['id'];

    protected $hidden = [
        'api_key',
        'service_account',
    ];

    protected $casts = [
        'provider' => TranslationProvider::class,
        'api_key' => 'encrypted',
        'service_account' => 'encrypted',
        'is_active' => 'boolean',
    ];

    protected $appends = ['provider_label', 'provider_type'];



    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function getProviderLabelAttribute(): string
    {
        return $this->provider->getLabel();
    }

    public function getProviderTypeAttribute(): string
    {
        return $this->provider->type()->value;
    }
}
