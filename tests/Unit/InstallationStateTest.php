<?php

use App\Support\Installation\InstallationState;

it('persists resumable progress and completion', function () {
    $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'weblex-install-'.bin2hex(random_bytes(5));
    mkdir($directory);
    $state = new InstallationState($directory, false, '1.0.0');

    expect($state->isInstalled())->toBeFalse();
    $state->saveProgress('migrating', ['attempt' => 1]);
    expect($state->progress()['step'])->toBe('migrating');

    $state->complete();
    expect($state->isInstalled())->toBeTrue()
        ->and($state->progress())->toBe([]);

    $state->reset();
    expect($state->isInstalled())->toBeFalse();

    foreach (glob($directory.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
        unlink($file);
    }
    rmdir($directory);
});

it('prevents concurrent installation operations', function () {
    $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'weblex-install-'.bin2hex(random_bytes(5));
    mkdir($directory);
    $state = new InstallationState($directory, false, '1.0.0');

    $state->exclusively(function () use ($state) {
        expect(fn () => $state->exclusively(fn () => null))
            ->toThrow(RuntimeException::class, 'Installation is already running.');
    });

    expect($state->unlock())->toBeTrue();
    foreach (glob($directory.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
        unlink($file);
    }
    rmdir($directory);
});
