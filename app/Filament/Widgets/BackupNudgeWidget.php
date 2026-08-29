<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use ShuvroRoy\FilamentSpatieLaravelBackup\Pages\Backups;
use Spatie\Backup\BackupDestination\Backup;
use Spatie\Backup\BackupDestination\BackupDestination;

class BackupNudgeWidget extends Widget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 15;

    protected string $view = 'filament.widgets.backup-nudge-widget';

    public static function canView(): bool
    {
        return static::newestBackup() === null;
    }

    protected function getViewData(): array
    {
        return [
            'backupsUrl' => Backups::getUrl(),
        ];
    }

    private static function newestBackup(): ?Backup
    {
        return BackupDestination::create(
            (string) config('backup.backup.destination.disks.0', 'backups'),
            (string) config('backup.backup.name'),
        )->newestBackup();
    }
}
