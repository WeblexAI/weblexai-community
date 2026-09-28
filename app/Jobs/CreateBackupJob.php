<?php

namespace App\Jobs;

use App\Models\BackupArchive;
use App\Settings\BackupSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use ShuvroRoy\FilamentSpatieLaravelBackup\Enums\BackupType;
use ShuvroRoy\FilamentSpatieLaravelBackup\FilamentSpatieLaravelBackup;
use Spatie\Backup\Commands\BackupCommand;
use Symfony\Component\Console\Command\Command;
use Throwable;

class CreateBackupJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        public readonly BackupType $type,
        public readonly ?int $timeout = null,
    ) {}

    public function handle(): void
    {
        $canSetTimeLimit = function_exists('set_time_limit');

        if ($this->timeout !== null && $canSetTimeLimit) {
            set_time_limit($this->timeout);
        }

        $password = app(BackupSettings::class)->archive_password;

        if (blank($password)) {
            throw new RuntimeException('The backup archive password has not been configured.');
        }

        $filename = $this->filename();

        $previousConfiguration = [
            'backup.backup.password' => config('backup.backup.password'),
            'backup.backup.encryption' => config('backup.backup.encryption'),
        ];

        config([
            'backup.backup.password' => $password,
            'backup.backup.encryption' => 'default',
        ]);

        try {
            $exitCode = Artisan::call(BackupCommand::class, [
                '--only-db' => $this->type === BackupType::ONLY_DATABASE,
                '--only-files' => $this->type === BackupType::ONLY_FILES,
                '--filename' => $filename,
                '--timeout' => $canSetTimeLimit ? $this->timeout : null,
            ]);
        } finally {
            config($previousConfiguration);
        }

        if ($exitCode !== Command::SUCCESS) {
            throw new RuntimeException("The backup command failed with exit code {$exitCode}.");
        }

        BackupArchive::query()->updateOrCreate(
            ['path' => config('backup.backup.name').'/'.$filename],
            ['archive_password' => $password],
        );

        try {
            FilamentSpatieLaravelBackup::clearBackupDestinationCaches();
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function filename(): string
    {
        return $this->type->value.'-'.now()->format('Y-m-d-H-i-s-u').'.zip';
    }
}
