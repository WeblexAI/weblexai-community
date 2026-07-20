<?php

use App\Enums\ModelStatus;
use App\Enums\TranslationModelType;
use App\Enums\TranslationProvider;
use App\Enums\UserRole;
use App\Filament\Resources\ProviderCredentials\Pages\CreateProviderCredential;
use App\Filament\Resources\ProviderCredentials\Pages\EditProviderCredential;
use App\Models\Project;
use App\Models\ProviderCredential;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

it('allows an administrator to create a provider credential', function () {
    $admin = User::factory()->create(['is_active' => ModelStatus::ACTIVE]);
    $admin->assignRole(UserRole::ADMIN->value);

    $this->actingAs($admin);

    Livewire::test(CreateProviderCredential::class)
        ->assertSuccessful()
        ->fillForm([
            'name' => 'Production OpenAI',
            'provider' => TranslationProvider::OPENAI->value,
            'api_key' => 'secret-provider-key',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $credential = ProviderCredential::query()->sole();

    expect($credential->user_id)->toBe($admin->id)
        ->and($credential->provider)->toBe(TranslationProvider::OPENAI)
        ->and($credential->api_key)->toBe('secret-provider-key')
        ->and($credential->model)->toBe(TranslationProvider::OPENAI->defaultModel())
        ->and($credential->base_url)->toBe(TranslationProvider::OPENAI->defaultBaseUrl());

    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'admin',
        'event' => 'created',
        'subject_type' => ProviderCredential::class,
        'subject_id' => $credential->id,
    ]);
});

it('allows an administrator to update a provider credential', function () {
    $admin = User::factory()->create(['is_active' => ModelStatus::ACTIVE]);
    $admin->assignRole(UserRole::ADMIN->value);
    $credential = $admin->providerCredentials()->create([
        'name' => 'OpenAI',
        'provider' => TranslationProvider::OPENAI,
        'api_key' => 'original-key',
        'model' => TranslationProvider::OPENAI->defaultModel(),
        'base_url' => TranslationProvider::OPENAI->defaultBaseUrl(),
        'is_active' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test(EditProviderCredential::class, ['record' => $credential->getRouteKey()])
        ->assertSuccessful()
        ->fillForm([
            'name' => 'Primary OpenAI',
            'provider' => TranslationProvider::OPENAI->value,
            'is_active' => true,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($credential->fresh())
        ->name->toBe('Primary OpenAI')
        ->api_key->toBe('original-key');
});

it('encrypts provider secrets and derives the translation type', function () {
    $user = User::factory()->create();
    $credential = ProviderCredential::query()->create([
        'user_id' => $user->id,
        'name' => 'Production OpenAI',
        'provider' => TranslationProvider::OPENAI,
        'api_key' => 'secret-provider-key',
        'model' => 'gpt-4.1-mini',
        'is_active' => true,
    ]);

    expect($credential->api_key)->toBe('secret-provider-key')
        ->and($credential->provider->type())->toBe(TranslationModelType::LLM)
        ->and(DB::table('provider_credentials')->where('id', $credential->id)->value('api_key'))
        ->not->toContain('secret-provider-key')
        ->and($credential->toArray())->not->toHaveKeys(['api_key', 'service_account']);
});

it('uses the assigned provider type to control LLM context', function () {
    $user = User::factory()->create(['force_password_change' => false]);
    $llm = $user->providerCredentials()->create([
        'name' => 'OpenAI',
        'provider' => TranslationProvider::OPENAI,
        'api_key' => 'secret',
        'model' => 'gpt-4.1-mini',
        'is_active' => true,
    ]);
    $project = Project::query()->create([
        'user_id' => $user->id,
        'name' => 'Provider project',
        'provider_credential_id' => $llm->id,
        'is_active' => ModelStatus::ACTIVE,
    ]);

    $this->actingAs($user)
        ->put(route('projects.translation-model.update', $project->slug), [
            'website_description' => 'A product documentation website.',
            'translation_tone' => 'FORMAL',
            'translation_audience' => 'TECHNICAL',
        ])
        ->assertSessionHasNoErrors();

    expect($project->fresh()->website_description)->toBe('A product documentation website.');
});

it('shows the required password change notice and blocks project access', function () {
    $user = User::factory()->create([
        'is_active' => ModelStatus::ACTIVE,
        'force_password_change' => true,
    ]);

    $this->actingAs($user)
        ->get('/projects')
        ->assertRedirect(route('profile'))
        ->assertSessionHas('message', 'Change your temporary password before continuing.');

    $this->actingAs($user)
        ->get('/profile')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Profile')
            ->where('user.force_password_change', true));
});
