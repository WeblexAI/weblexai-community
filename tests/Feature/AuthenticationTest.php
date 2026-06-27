<?php

use App\Enums\ModelStatus;
use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

it('shows administrators where to sign in', function () {
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
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', 'Administrators sign in through /admin.');

    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('auth/Login')
            ->where('error', 'Administrators sign in through /admin.'));

    $this->assertGuest();
});
