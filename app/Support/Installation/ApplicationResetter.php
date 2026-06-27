<?php

namespace App\Support\Installation;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ApplicationResetter
{
    public function __construct(
        private readonly InstallationState $state,
        private readonly EnvironmentFileWriter $environment,
        private readonly ?array $localDataDirectories = null,
    ) {}

    public function reset(int $administratorId, string $administratorEmail): void
    {
        $this->state->exclusively(function () use ($administratorId, $administratorEmail): void {
            $this->environment->write(['APP_INSTALLED' => false]);

            Log::critical('Application reset started by an administrator.', [
                'administrator_id' => $administratorId,
                'administrator_email' => $administratorEmail,
            ]);

            try {
                $exitCode = Artisan::call('migrate:fresh', [
                    '--drop-views' => true,
                    '--drop-types' => true,
                    '--force' => true,
                ]);

                if ($exitCode !== 0) {
                    throw new RuntimeException('Fresh database migration failed.');
                }

                $this->clearLocalApplicationData();
            } catch (Throwable $exception) {
                Log::critical('Application reset failed after database reset started.', [
                    'administrator_id' => $administratorId,
                    'administrator_email' => $administratorEmail,
                    'exception' => $exception,
                ]);

                throw $exception;
            } finally {
                $this->state->reset();
            }
        });
    }

    private function clearLocalApplicationData(): void
    {
        Cache::flush();

        $directories = $this->localDataDirectories ?? [
            storage_path('app/public'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
        ];

        foreach ($directories as $directory) {
            File::ensureDirectoryExists($directory);
            File::cleanDirectory($directory);
        }
    }
}
