<?php

use App\Enums\ModelStatus;
use App\Enums\TranslationModelType;
use App\Enums\TranslationProvider;
use App\Models\Project;
use App\Models\ProviderCredential;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
