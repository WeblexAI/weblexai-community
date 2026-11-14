<?php

use App\Enums\ModelStatus;
use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

it('allows administrators to sign in through the normal login screen', function () {
    $admin = User::factory()->create([
        'email' => 'admin@example.com',
        'password' => 'AdminPassword123!',
        'is_active' => ModelStatus::ACTIVE,
    ]);
    $admin->assignRole(UserRole::ADMIN->value);

    $response = $this
        ->from(route('login'))
        ->post(route('login'), [
            'email' => 'admin@example.com',
            'password' => 'AdminPassword123!',
        ]);

    $response
        ->assertRedirect('/admin');

    $this->assertAuthenticatedAs($admin);
});

it('uses a full page navigation when an administrator signs in through Inertia', function () {
    $admin = User::factory()->create(['password' => 'AdminPassword123!', 'is_active' => ModelStatus::ACTIVE]);
    $admin->assignRole(UserRole::ADMIN->value);

    $this->withHeader('X-Inertia', 'true')->post(route('login'), [
        'email' => $admin->email,
        'password' => 'AdminPassword123!',
    ])->assertStatus(409)->assertHeader('X-Inertia-Location', url('/admin'));

    $this->assertAuthenticatedAs($admin);
});
