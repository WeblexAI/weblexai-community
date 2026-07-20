<?php

use Dotenv\Parser\Entry;
use Dotenv\Parser\Parser;

it('stores dashboard backups on the dedicated backup disk', function () {
    expect(config('backup.backup.destination.disks'))
        ->toBe(['backups'])
        ->and(config('backup.monitor_backups.0.disks'))
        ->toBe(['backups'])
        ->and(config('filesystems.disks.backups.root'))
        ->toBe(storage_path('app/backups'));
});

it('uses a host-writable backup path in the environment template', function () {
    $contents = file_get_contents(base_path('.env.example'));

    expect($contents)->toBeString();

    $backupPath = collect((new Parser)->parse($contents))
        ->first(fn (Entry $entry): bool => $entry->getName() === 'BACKUP_PATH');

    expect($backupPath)->toBeInstanceOf(Entry::class)
        ->and($backupPath->getValue()->get()->getChars())
        ->toBe('');
});
