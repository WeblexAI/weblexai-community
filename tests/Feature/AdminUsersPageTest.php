<?php

use App\Enums\ModelStatus;
use App\Enums\UserRole;
use App\Models\User;

it('renders the administrator users table', function () {
    $admin = User::factory()->create(['is_active' => ModelStatus::ACTIVE]);
    $admin->assignRole(UserRole::ADMIN->value);

    $this->actingAs($admin)
        ->get('/admin/users')
        ->assertOk()
        ->assertSee('Force password change');
});

it('renders the administrator user details', function () {
    $admin = User::factory()->create(['is_active' => ModelStatus::ACTIVE]);
    $admin->assignRole(UserRole::ADMIN->value);
    $user = User::factory()->create(['force_password_change' => true]);
    $user->assignRole(UserRole::USER->value);

    $this->actingAs($admin)
        ->get("/admin/users/{$user->getRouteKey()}")
        ->assertOk()
        ->assertSee($user->email)
        ->assertSee('Force password change');
});

it('renders the administrator user creation form', function () {
    $admin = User::factory()->create(['is_active' => ModelStatus::ACTIVE]);
    $admin->assignRole(UserRole::ADMIN->value);

    $this->actingAs($admin)
        ->get('/admin/users/create')
        ->assertOk()
        ->assertSee('Create User');
});
