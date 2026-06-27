<?php

use App\Support\Installation\EnvironmentFileWriter;

it('preserves unknown keys and an existing application key', function () {
    $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'weblex-env-'.bin2hex(random_bytes(5));
    mkdir($directory);
    $path = $directory.DIRECTORY_SEPARATOR.'.env';
    file_put_contents($path, "APP_KEY=\"base64:existing\"\nCUSTOM_SETTING=\"keep\"\nDB_PASSWORD=\"old\"\n");

    $key = (new EnvironmentFileWriter($path))->write([
        'DB_PASSWORD' => 'new $ecret "value"',
        'APP_URL' => 'https://example.com',
    ]);

    $contents = file_get_contents($path);
    expect($key)->toBe('base64:existing')
        ->and($contents)->toContain('CUSTOM_SETTING="keep"')
        ->and($contents)->toContain('DB_PASSWORD="new \\$ecret \\"value\\""')
        ->and($contents)->toContain('APP_URL="https://example.com"');

    foreach (glob($directory.DIRECTORY_SEPARATOR.'.*') ?: [] as $file) {
        if (! in_array(basename($file), ['.', '..'], true)) {
            unlink($file);
        }
    }
    rmdir($directory);
});

it('generates an application key and rejects line breaks', function () {
    $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'weblex-env-'.bin2hex(random_bytes(5));
    mkdir($directory);
    $path = $directory.DIRECTORY_SEPARATOR.'.env';
    $writer = new EnvironmentFileWriter($path);

    expect($writer->write(['APP_NAME' => 'WeblexAI']))->toStartWith('base64:')
        ->and(fn () => $writer->write(['APP_NAME' => "bad\nvalue"]))->toThrow(RuntimeException::class);

    foreach (glob($directory.DIRECTORY_SEPARATOR.'.*') ?: [] as $file) {
        if (! in_array(basename($file), ['.', '..'], true)) {
            unlink($file);
        }
    }
    rmdir($directory);
});
