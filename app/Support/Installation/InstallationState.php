<?php

namespace App\Support\Installation;

use RuntimeException;

class InstallationState
{
    public function __construct(
        private readonly ?string $directory = null,
        private readonly ?bool $configuredInstalled = null,
        private readonly ?string $version = null,
    ) {}

    public function isInstalled(): bool
    {
        $configured = $this->configuredInstalled ?? (bool) config('community.installed');

        return $configured || is_file($this->completedPath());
    }

    public function progress(): array
    {
        if (! is_file($this->progressPath())) {
            return [];
        }

        return json_decode((string) file_get_contents($this->progressPath()), true) ?: [];
    }

    public function saveProgress(string $step, array $context = []): void
    {
        $this->writeJson($this->progressPath(), [
            'step' => $step,
            'updated_at' => now()->toIso8601String(),
            'context' => $context,
        ]);
    }

    public function complete(): void
    {
        $this->writeJson($this->completedPath(), [
            'version' => $this->version ?? config('community.version'),
            'installed_at' => now()->toIso8601String(),
        ]);

        @unlink($this->progressPath());
    }

    public function reset(): void
    {
        if (app()->bound('config')) {
            config(['community.installed' => false]);
        }

        foreach ([$this->completedPath(), $this->progressPath()] as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function unlock(): bool
    {
        return ! is_file($this->lockPath()) || unlink($this->lockPath());
    }

    public function exclusively(callable $operation): mixed
    {
        $handle = fopen($this->lockPath(), 'c+');

        if ($handle === false || ! flock($handle, LOCK_EX | LOCK_NB)) {
            throw new RuntimeException('Installation is already running.');
        }

        try {
            return $operation();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function completedPath(): string
    {
        return $this->path('installed');
    }

    private function progressPath(): string
    {
        return $this->path('installation.json');
    }

    private function lockPath(): string
    {
        return $this->path('installation.lock');
    }

    private function path(string $file): string
    {
        return ($this->directory ?? storage_path('app')).DIRECTORY_SEPARATOR.$file;
    }

    private function writeJson(string $path, array $data): void
    {
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        $temporary = $path.'.tmp';
        $written = file_put_contents(
            $temporary,
            json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
            LOCK_EX,
        );

        if ($written === false || ! rename($temporary, $path)) {
            @unlink($temporary);
            throw new RuntimeException("Unable to persist installation state at {$path}.");
        }
    }
}
