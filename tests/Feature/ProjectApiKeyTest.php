<?php

use App\Enums\ModelStatus;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use Inertia\Testing\AssertableInertia;

it('stores project API keys encrypted and authenticates with their hash', function () {
    $owner = User::factory()->create();
    $project = Project::query()->create([
        'user_id' => $owner->id,
        'name' => 'Documentation',
    ]);

    expect($project->api_key)
        ->not->toBeNull()
        ->and($project->api_key_hash)->toBe(hash('sha256', $project->api_key))
        ->and($project->getRawOriginal('api_key'))->not->toBe($project->api_key);
});

it('shows the API key on the authorized project setup page', function () {
    $this->withoutVite();

    $owner = User::factory()->create();
    $project = Project::query()->create([
        'user_id' => $owner->id,
        'name' => 'Documentation',
    ]);

    $this->actingAs($owner)
        ->get(route('projects.setup', $project->slug))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Project/Setup')
            ->where('apiKey', $project->api_key));
});

it('shows the API key and project detail tabs to administrators', function () {
    $admin = User::factory()->create(['is_active' => ModelStatus::ACTIVE]);
    $admin->assignRole(UserRole::ADMIN->value);
    $project = Project::query()->create([
        'user_id' => $admin->id,
        'name' => 'Documentation',
    ]);

    $this->actingAs($admin)
        ->get("/admin/projects/{$project->getRouteKey()}")
        ->assertOk()
        ->assertSee($project->api_key)
        ->assertSee('Overview')
        ->assertSee('Integration')
        ->assertSee('Languages');
});

it('persists a retrievable API key when rotating it', function () {
    $owner = User::factory()->create();
    $project = Project::query()->create([
        'user_id' => $owner->id,
        'name' => 'Documentation',
    ]);

    $apiKey = ProjectService::rotateApiKey($project);
    $project->refresh();

    expect($project->api_key)->toBe($apiKey)
        ->and($project->api_key_hash)->toBe(hash('sha256', $apiKey));
});
