<?php

namespace App\Models;

use App\Enums\ModelStatus;
use App\Pivots\CollaboratorProjectPivot;
use App\Traits\BaseModelTrait;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Traits\CausesActivity;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $email
 * @property string $password
 * @property ModelStatus $is_active
 * @property bool $force_password_change
 * @property string|null $app_authentication_secret
 * @property array|null $app_authentication_recovery_codes
 * @property string|null $remember_token
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property-read string $formatted_created_at
 */
class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, HasName
{
    use BaseModelTrait, CausesActivity, HasFactory, HasRoles, Notifiable;

    protected $guarded = ['id', 'uuid', 'created_at', 'updated_at'];

    protected $hidden = [
        'password',
        'remember_token',
        'app_authentication_secret',
        'app_authentication_recovery_codes',
    ];

    protected $casts = [
        'password' => 'hashed',
        'is_active' => ModelStatus::class,
        'force_password_change' => 'boolean',
        'app_authentication_secret' => 'encrypted',
        'app_authentication_recovery_codes' => 'encrypted:array',
    ];

    protected $appends = ['formatted_created_at'];

    
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function providerCredentials(): HasMany
    {
        return $this->hasMany(ProviderCredential::class);
    }

    public function collaboratedProjects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_user')
            ->using(CollaboratorProjectPivot::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active === ModelStatus::ACTIVE && $this->hasRole('admin');
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function getAppAuthenticationSecret(): ?string
    {
        return $this->app_authentication_secret;
    }

    public function saveAppAuthenticationSecret(?string $secret): void
    {
        $this->forceFill(['app_authentication_secret' => $secret])->save();
    }

    public function getAppAuthenticationHolderName(): string
    {
        return $this->email;
    }

    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        return $this->app_authentication_recovery_codes;
    }

    public function saveAppAuthenticationRecoveryCodes(?array $codes): void
    {
        $this->forceFill(['app_authentication_recovery_codes' => $codes])->save();
    }
}
