<?php

use App\Enums\ModelStatus;
use App\Enums\UserRole;
use App\Filament\Pages\ResetApplication;
use App\Models\User;
use App\Support\Installation\ApplicationResetter;
use App\Support\Installation\EnvironmentFileWriter;
use App\Support\Installation\InstallationState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

it('renders the application reset page for administrators', function () {
    $admin = User::factory()->create(['is_active' => ModelStatus::ACTIVE]);
    $admin->assignRole(UserRole::ADMIN->value);

    $this->actingAs($admin)
        ->get('/admin/reset-application')
        ->assertOk()
        ->assertSee('Start with a clean installation')
        ->assertSee('Reset application');
});

it('requires the administrator password and exact confirmation phrase', function () {
    $admin = User::factory()->create([
        'is_active' => ModelStatus::ACTIVE,
        'password' => 'AdministratorPassword123!',
    ]);
    $admin->assignRole(UserRole::ADMIN->value);

    $this->actingAs($admin);

    Livewire::test(ResetApplication::class)
        ->callAction('resetApplication', [
            'current_password' => 'incorrect-password',
            'confirmation' => 'RESET',
        ])
        ->assertHasActionErrors([
            'current_password',
            'confirmation',
        ]);
});

it('resets the application and redirects to the installer', function () {
    $admin = User::factory()->create([
        'is_active' => ModelStatus::ACTIVE,
        'password' => 'AdministratorPassword123!',
    ]);
    $admin->assignRole(UserRole::ADMIN->value);

    $resetter = Mockery::mock(ApplicationResetter::class);
    $resetter->shouldReceive('reset')
        ->once()
        ->with($admin->id, $admin->email);
    app()->instance(ApplicationResetter::class, $resetter);

    $this->actingAs($admin);

    Livewire::test(ResetApplication::class)
        ->callAction('resetApplication', [
            'current_password' => 'AdministratorPassword123!',
            'confirmation' => 'RESET WEBLEXAI',
        ])
        ->assertRedirect(route('install.show'));

    $this->assertGuest();
});

it('runs fresh migrations, clears local data, and unlocks the installer', function () {
    $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'weblex-reset-'.bin2hex(random_bytes(5));
    $stateDirectory = $directory.DIRECTORY_SEPARATOR.'state';
    $dataDirectories = [
        $directory.DIRECTORY_SEPARATOR.'public',
        $directory.DIRECTORY_SEPARATOR.'cache',
        $directory.DIRECTORY_SEPARATOR.'sessions',
        $directory.DIRECTORY_SEPARATOR.'views',
    ];
    $environmentPath = $directory.DIRECTORY_SEPARATOR.'.env';

    File::ensureDirectoryExists($stateDirectory);
    foreach ($dataDirectories as $dataDirectory) {
        File::ensureDirectoryExists($dataDirectory);
        File::put($dataDirectory.DIRECTORY_SEPARATOR.'application-data', 'delete me');
    }
    File::put($environmentPath, "APP_KEY=\"base64:test\"\nAPP_INSTALLED=true\n");

    $state = new InstallationState($stateDirectory, false, '1.0.0');
    $state->complete();

    Artisan::shouldReceive('call')
        ->once()
        ->with('migrate:fresh', [
            '--drop-views' => true,
            '--drop-types' => true,
            '--force' => true,
        ])
        ->andReturn(0);
    Cache::shouldReceive('flush')->once()->andReturnTrue();

    $resetter = new ApplicationResetter(
        $state,
        new EnvironmentFileWriter($environmentPath),
        $dataDirectories,
    );

    $resetter->reset(42, 'admin@example.com');

    expect($state->isInstalled())->toBeFalse()
        ->and(File::get($environmentPath))->toContain('APP_INSTALLED=false');

    foreach ($dataDirectories as $dataDirectory) {
        expect(File::files($dataDirectory))->toBeEmpty();
    }

    File::deleteDirectory($directory);
});
