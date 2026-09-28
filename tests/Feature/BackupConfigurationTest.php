<?php

it('stores dashboard backups on the dedicated backup disk', function () {
    expect(config('backup.backup.destination.disks'))
        ->toBe(['backups'])
        ->and(config('backup.monitor_backups.0.disks'))
        ->toBe(['backups'])
        ->and(config('filesystems.disks.backups.root'))
        ->toBe(storage_path('app/backups'));
});

it('keeps archive encryption and retention limits out of the public environment template', function () {
    $contents = file_get_contents(base_path('.env.example'));

    expect($contents)->toBeString();

    expect(config('backup.backup.password'))->toBeNull()
        ->and(config('backup.backup.encryption'))->toBe('none')
        ->and(config('backup.cleanup.default_strategy.delete_oldest_backups_when_using_more_megabytes_than'))->toBeNull()
        ->and($contents)->not->toContain('BACKUP_NAME=')
        ->and($contents)->not->toContain('BACKUP_DISK=')
        ->and($contents)->not->toContain('BACKUP_PATH=')
        ->and($contents)->not->toContain('BACKUP_ARCHIVE_PASSWORD=')
        ->and($contents)->not->toContain('BACKUP_MAX_AGE_DAYS=')
        ->and($contents)->not->toContain('BACKUP_MAX_STORAGE_MB=');
});

it('does not configure backup email notifications', function () {
    $contents = file_get_contents(base_path('.env.example'));

    expect(config('backup.notifications.notifications'))->toBe([])
        ->and($contents)->not->toContain('BACKUP_NOTIFICATIONS_ENABLED=')
        ->and($contents)->not->toContain('BACKUP_NOTIFICATION_EMAIL=');
});
