<?php

namespace App\Support\Installation;

use RuntimeException;

class EnvironmentFileWriter
{
    public function __construct(private readonly ?string $path = null) {}

    public function write(array $values): string
    {
        $configuredPath = $this->path ?? base_path('.env');
        $path = is_file($configuredPath) ? (realpath($configuredPath) ?: $configuredPath) : $configuredPath;
        $existing = is_file($path) ? (string) file_get_contents($path) : '';
        $backup = $path.'.backup-'.now()->format('YmdHis');
        $values['APP_KEY'] = $this->existingAppKey($existing) ?: 'base64:'.base64_encode(random_bytes(32));
        $contents = $this->merge($existing, $values);
        $temporary = $path.'.tmp-'.bin2hex(random_bytes(6));

        if (is_file($path) && ! is_writable($path)) {
            throw new RuntimeException('The .env file is read-only. Make it writable or configure the environment outside the container.');
        }

        if (! is_writable(dirname($path))) {
            throw new RuntimeException('The application directory is not writable, so .env cannot be created.');
        }

        if (is_file($path) && ! copy($path, $backup)) {
            throw new RuntimeException('Unable to create a .env backup.');
        }

        if (file_put_contents($temporary, $contents, LOCK_EX) === false) {
            throw new RuntimeException('Unable to write the temporary environment file.');
        }

        if (! rename($temporary, $path)) {
            @unlink($temporary);
            throw new RuntimeException('Unable to replace the environment file atomically.');
        }

        return $values['APP_KEY'];
    }

    private function merge(string $existing, array $values): string
    {
        $lines = $existing === '' ? [] : preg_split('/\R/', rtrim($existing));
        $replaced = [];

        foreach ($lines as &$line) {
            if (! preg_match('/^([A-Z][A-Z0-9_]*)=/', $line, $matches)) {
                continue;
            }

            $key = $matches[1];
            if (array_key_exists($key, $values)) {
                $line = $key.'='.$this->quote($values[$key]);
                $replaced[$key] = true;
            }
        }
        unset($line);

        foreach ($values as $key => $value) {
            if (! isset($replaced[$key])) {
                $lines[] = $key.'='.$this->quote($value);
            }
        }

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    private function quote(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        $value = (string) $value;
        if (str_contains($value, "\n") || str_contains($value, "\r")) {
            throw new RuntimeException('Environment values cannot contain line breaks.');
        }

        return '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value).'"';
    }

    private function existingAppKey(string $contents): ?string
    {
        if (preg_match('/^APP_KEY=(?:"([^"]+)"|([^\r\n]+))/m', $contents, $matches)) {
            return trim($matches[1] ?: $matches[2]);
        }

        return null;
    }
}
