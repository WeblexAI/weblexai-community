<?php

namespace App\Console\Commands;

use App\Updates\ReleaseFeed;
use App\Updates\UpdateManager;
use Illuminate\Console\Command;
use Throwable;

class UpdateCommunityEdition extends Command
{
    protected $signature = 'weblex:update {--check : Only check for an update} {--driver= : Override the configured update driver}';

    protected $description = 'Check for and apply a signed WeblexAI Community Edition update';

    public function handle(ReleaseFeed $feed, UpdateManager $updates): int
    {
        try {
            if ($this->option('driver')) {
                config(['community.update_driver' => $this->option('driver')]);
            }

            $release = $feed->latest(force: true);
            if (! $release) {
                $this->warn('No release feed is configured.');

                return self::FAILURE;
            }

            if (! $feed->updateAvailable($release)) {
                $this->info('WeblexAI Community Edition is up to date.');

                return self::SUCCESS;
            }

            $this->info("Version {$release->version} is available.");
            if ($this->option('check')) {
                return self::SUCCESS;
            }

            if (! $feed->isCompatible($release)) {
                $this->error('This host does not satisfy the release prerequisites.');

                return self::FAILURE;
            }

            $updates->apply($release);
            $this->info('The update was started successfully.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
