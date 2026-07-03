<?php

it('stores dashboard backups on the dedicated backup disk', function () {
    expect(config('backup.backup.destination.disks'))
        ->toBe(['backups'])
        ->and(config('backup.monitor_backups.0.disks'))
        ->toBe(['backups'])
        ->and(config('filesystems.disks.backups.root'))
        ->not->toBe(config('filesystems.disks.local.root'));
});
