<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('a user can change their password with the current password', function () {
    $user = User::factory()->create([
        'password' => 'CurrentPassword123!',
        'force_password_change' => true,
    ]);

    $response = $this->actingAs($user)->put(route('profile.password'), [
        'current_password' => 'CurrentPassword123!',
        'password' => 'ReplacementPassword123!',
        'password_confirmation' => 'ReplacementPassword123!',
    ]);

    $response->assertRedirect();
    expect(Hash::check('ReplacementPassword123!', $user->fresh()->password))->toBeTrue()
        ->and($user->fresh()->force_password_change)->toBeFalse();
});

test('the current password is always required', function () {
    $user = User::factory()->create(['password' => 'CurrentPassword123!']);

    $response = $this
        ->actingAs($user)
        ->from(route('profile'))
        ->put(route('profile.password'), [
            'password' => 'ReplacementPassword123!',
            'password_confirmation' => 'ReplacementPassword123!',
        ]);

    $response->assertRedirect(route('profile'))->assertSessionHasErrors('current_password');
    expect(Hash::check('CurrentPassword123!', $user->fresh()->password))->toBeTrue();
});
