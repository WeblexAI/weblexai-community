<?php

use App\Enums\ModelStatus;
use App\Enums\UserRole;
use App\Filament\Backup\Backups;
use App\Jobs\CreateBackupJob;
use App\Models\BackupArchive;
use App\Models\User;
use App\Settings\BackupSettings;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use ShuvroRoy\FilamentSpatieLaravelBackup\Enums\BackupType;
use Spatie\Backup\Commands\BackupCommand;
use Symfony\Component\Console\Command\Command;

it('saves the administrator backup password', function () {
    $admin = User::factory()->create(['is_active' => ModelStatus::ACTIVE]);
    $admin->assignRole(UserRole::ADMIN->value);
    $this->actingAs($admin);

    Livewire::test(Backups::class)
        ->callAction('setBackupPassword', data: [
            'archive_password' => 'Strong-backup-password-123!',
            'archive_password_confirmation' => 'Strong-backup-password-123!',
        ])
        ->assertNotified('Archive password saved');

    expect(app(BackupSettings::class)->archive_password)
        ->toBe('Strong-backup-password-123!');
});

it('queues a backup using the saved password', function () {
    $admin = User::factory()->create(['is_active' => ModelStatus::ACTIVE]);
    $admin->assignRole(UserRole::ADMIN->value);
    $this->actingAs($admin);

    $settings = app(BackupSettings::class);
    $settings->archive_password = 'Strong-backup-password-123!';
    $settings->save();

    Queue::fake();

    Livewire::test(Backups::class)
        ->callAction('createBackup', data: [
            'type' => BackupType::DATABASE_AND_FILES->value,
        ])
        ->assertNotified('Backup queued');

    Queue::assertPushed(CreateBackupJob::class, function (CreateBackupJob $job): bool {
        return $job->type === BackupType::DATABASE_AND_FILES;
    });
});

it('runs the backup command with the saved password and records the archive key', function () {
    $settings = app(BackupSettings::class);
    $settings->archive_password = 'Archive-password-123!';
    $settings->save();

    config([
        'backup.backup.password' => 'previous-password',
        'backup.backup.encryption' => 'none',
    ]);

    $filename = null;

    Artisan::shouldReceive('call')
        ->once()
        ->with(BackupCommand::class, Mockery::on(function (array $arguments) use (&$filename): bool {
            $filename = $arguments['--filename'];

            return $arguments['--only-db'] === true
                && $arguments['--only-files'] === false
                && str_starts_with($filename, 'only-db-')
                && str_ends_with($filename, '.zip');
        }))
        ->andReturn(Command::SUCCESS);

    (new CreateBackupJob(
        BackupType::ONLY_DATABASE,
        30,
    ))->handle();

    expect(config('backup.backup.password'))->toBe('previous-password')
        ->and(config('backup.backup.encryption'))->toBe('none')
        ->and($filename)->not->toBeNull();

    $archive = BackupArchive::query()->firstOrFail();

    expect($archive->path)->toBe(config('backup.backup.name').'/'.$filename)
        ->and($archive->archive_password)->toBe('Archive-password-123!');
});
