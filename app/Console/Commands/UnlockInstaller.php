<?php

namespace App\Console\Commands;

use App\Support\Installation\InstallationState;
use Illuminate\Console\Command;

class UnlockInstaller extends Command
{
    protected $signature = 'weblex:install:unlock {--force : Remove the installer lock without confirmation}';

    protected $description = 'Remove a stale browser installer lock';

    public function handle(InstallationState $state): int
    {
        if (! $this->option('force') && ! $this->confirm('Remove the installer lock? Only continue when no installation is running.')) {
            return self::FAILURE;
        }

        if (! $state->unlock()) {
            $this->error('The installer lock could not be removed.');

            return self::FAILURE;
        }

        $this->info('Installer lock removed.');

        return self::SUCCESS;
    }
}
