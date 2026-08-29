<?php

use App\Enums\ModelStatus;
use App\Enums\UserRole;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('renders operational admin dashboard widgets', function () {
    $admin = User::factory()->create(['is_active' => ModelStatus::ACTIVE]);
    $admin->assignRole(UserRole::ADMIN->value);

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertSee('System overview')
        ->assertSee('Project readiness')
        ->assertSee('Recent admin activity')
        ->assertSee('Start tour');
});

it('shows the backup nudge when no backup exists and hides it after the first backup', function () {
    $admin = User::factory()->create(['is_active' => ModelStatus::ACTIVE]);
    $admin->assignRole(UserRole::ADMIN->value);

    Storage::disk('backups')->deleteDirectory(config('backup.backup.name'));

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertSee('No backups exist yet');

    Storage::disk('backups')->put(config('backup.backup.name').'/weblex-test.zip', 'fixture');

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertDontSee('No backups exist yet');
});

it('renders backup and health administration pages for administrators', function () {
    $admin = User::factory()->create(['is_active' => ModelStatus::ACTIVE]);
    $admin->assignRole(UserRole::ADMIN->value);

    $this->actingAs($admin)
        ->get('/admin/backups')
        ->assertOk()
        ->assertSee('Backups');

    $this->actingAs($admin)
        ->get('/admin/health-check-results')
        ->assertOk()
        ->assertSee('Health');
});

it('assigns backup permissions to the administrator role', function () {
    $role = Role::findByName(UserRole::ADMIN->value);

    expect(Permission::whereIn('name', [
        'create-backup',
        'download-backup',
        'delete-backup',
    ])->count())->toBe(3)
        ->and($role->hasPermissionTo('create-backup'))->toBeTrue()
        ->and($role->hasPermissionTo('download-backup'))->toBeTrue()
        ->and($role->hasPermissionTo('delete-backup'))->toBeTrue();
});
