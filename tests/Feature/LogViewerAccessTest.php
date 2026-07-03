<?php

use App\Enums\ModelStatus;
use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('allows active administrators to view application logs', function () {
    $admin = User::factory()->create(['is_active' => ModelStatus::ACTIVE]);
    $admin->assignRole(UserRole::ADMIN->value);

    $this->actingAs($admin)
        ->get('/log-viewer')
        ->assertOk();
});

it('opens application logs from the admin navigation route', function () {
    $admin = User::factory()->create(['is_active' => ModelStatus::ACTIVE]);
    $admin->assignRole(UserRole::ADMIN->value);

    $this->actingAs($admin)
        ->get('/admin/logs')
        ->assertRedirect('/log-viewer');
});

it('keeps the log viewer api authenticated when the browser uses a local port', function () {
    config(['app.url' => 'http://localhost']);

    $admin = User::factory()->create(['is_active' => ModelStatus::ACTIVE]);
    $admin->assignRole(UserRole::ADMIN->value);

    $this->actingAs($admin)
        ->withHeader('referer', 'http://localhost:8787/log-viewer')
        ->getJson('/log-viewer/api/folders')
        ->assertOk();
});

it('denies application log access to regular users', function () {
    $user = User::factory()->create(['is_active' => ModelStatus::ACTIVE]);
    $user->assignRole(UserRole::USER->value);

    $this->actingAs($user)
        ->get('/log-viewer')
        ->assertForbidden();
});

it('denies the admin log navigation route to regular users', function () {
    $user = User::factory()->create(['is_active' => ModelStatus::ACTIVE]);
    $user->assignRole(UserRole::USER->value);

    $this->actingAs($user)
        ->get('/admin/logs')
        ->assertForbidden();
});

it('denies application log access to inactive administrators', function () {
    $admin = User::factory()->create(['is_active' => ModelStatus::INACTIVE]);
    $admin->assignRole(UserRole::ADMIN->value);

    $this->actingAs($admin)
        ->get('/log-viewer')
        ->assertForbidden();
});
