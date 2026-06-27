<?php

use App\Enums\CollaboratorRole;
use App\Enums\ModelStatus;
use App\Enums\TranslationProvider;
use App\Enums\UserRole;
use App\Models\Language;
use App\Models\Project;
use App\Models\ProviderCredential;
use App\Models\User;
use App\Services\ProjectService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

$admin = User::query()->where('email', 'admin@example.test')->firstOrFail();

$credential = ProviderCredential::query()->updateOrCreate(
    ['user_id' => $admin->id, 'name' => 'E2E mock provider'],
    [
        'provider' => TranslationProvider::QWEN,
        'api_key' => 'mock-provider-key',
        'base_url' => 'http://mock-provider:8081/v1',
        'model' => 'mock-qwen',
        'is_active' => true,
    ],
);

$manager = User::query()->updateOrCreate(
    ['email' => 'manager@example.test'],
    [
        'name' => 'E2E Manager',
        'password' => Hash::make('E2e-Password-123!'),
        'is_active' => ModelStatus::ACTIVE,
        'force_password_change' => false,
        'created_by_id' => $admin->id,
    ],
);
$manager->syncRoles([UserRole::USER->value]);

$english = Language::query()->where('iso_2', 'en')->firstOrFail();
$french = Language::query()->where('iso_2', 'fr')->firstOrFail();

$project = Project::query()->where('name', 'E2E Project')->first();

if (! $project) {
    $project = Project::query()->create([
        'name' => 'E2E Project',
        'user_id' => $admin->id,
        'created_by_id' => $admin->id,
        'original_language_id' => $english->id,
        'provider_credential_id' => $credential->id,
        'is_active' => ModelStatus::ACTIVE,
        'should_display_automatics' => true,
        'website_description' => 'Repeatable Docker E2E fixture.',
    ]);

    $apiKey = $project->api_key;
} else {
    $project->update(['provider_credential_id' => $credential->id]);
    $apiKey = app(ProjectService::class)->rotateApiKey($project);
}

$project->languages()->syncWithoutDetaching([
    $french->id => [
        'is_public' => true,
        'should_display_automatics' => true,
        'is_disabled' => false,
    ],
]);

$project->acceptedOrigins()->updateOrCreate(
    ['origin' => 'http://fixture.test'],
    ['created_by_id' => $admin->id],
);

$project->collaborators()->syncWithoutDetaching([
    $manager->id => ['role' => CollaboratorRole::MANAGER->value],
]);

Storage::disk('local')->put('e2e-sentinel.txt', 'restored');
file_put_contents('/tmp/weblex-e2e-api-key', $apiKey);
file_put_contents('/tmp/weblex-e2e-fixture.json', json_encode([
    'project_id' => $project->id,
    'admin_id' => $admin->id,
    'manager_id' => $manager->id,
    'origin' => 'http://fixture.test',
], JSON_PRETTY_PRINT));

echo "Prepared E2E fixture for project {$project->id}.\n";
