<?php

namespace App\Filament\Backup;

use App\Jobs\CreateBackupJob;
use App\Settings\BackupSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use LogicException;
use ShuvroRoy\FilamentSpatieLaravelBackup\Enums\BackupType;
use ShuvroRoy\FilamentSpatieLaravelBackup\FilamentSpatieLaravelBackup;
use ShuvroRoy\FilamentSpatieLaravelBackup\FilamentSpatieLaravelBackupPlugin;
use ShuvroRoy\FilamentSpatieLaravelBackup\Pages\Backups as VendorBackups;

class Backups extends VendorBackups
{
    protected string $view = 'filament.pages.backups';

    public function create(string $type = BackupType::DATABASE_AND_FILES->value): void
    {
        throw new LogicException('Backups must be created through the backup action.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createBackup')
                ->label('Create backup')
                ->schema([
                    Select::make('type')
                        ->label('Backup contents')
                        ->options(FilamentSpatieLaravelBackup::getFilterTypes())
                        ->default(BackupType::DATABASE_AND_FILES->value)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $backupType = BackupType::tryFrom((string) ($data['type'] ?? ''));

                    if ($backupType === null) {
                        Notification::make()
                            ->title('Select valid backup contents.')
                            ->danger()
                            ->send();

                        return;
                    }

                    if (blank(app(BackupSettings::class)->archive_password)) {
                        Notification::make()
                            ->title('Set the archive password first.')
                            ->body('Save a password before creating a backup.')
                            ->danger()
                            ->send();

                        return;
                    }

                    /** @var FilamentSpatieLaravelBackupPlugin $plugin */
                    $plugin = filament()->getPlugin('filament-spatie-backup');

                    CreateBackupJob::dispatch(
                        $backupType,
                        $plugin->getTimeout(),
                    )
                        ->onConnection($plugin->getQueueConnection())
                        ->onQueue($plugin->getQueue());

                    Notification::make()
                        ->title('Backup queued')
                        ->body('The archive will appear here when it is ready.')
                        ->success()
                        ->send();
                })
                ->visible(auth()->user()?->can('create-backup') ?? false),
            Action::make('setBackupPassword')
                ->label('Set archive password')
                ->schema([
                    TextInput::make('archive_password')
                        ->label('Archive password')
                        ->password()
                        ->revealable()
                        ->confirmed()
                        ->minLength(12)
                        ->default(fn (): string => app(BackupSettings::class)->archive_password)
                        ->helperText('This password is used for every new backup. Changing it does not change existing archives.')
                        ->required(),
                    TextInput::make('archive_password_confirmation')
                        ->label('Confirm archive password')
                        ->password()
                        ->revealable()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $settings = app(BackupSettings::class);
                    $settings->archive_password = (string) $data['archive_password'];
                    $settings->save();

                    Notification::make()
                        ->title('Archive password saved')
                        ->success()
                        ->send();
                })
                ->visible(auth()->user()?->can('create-backup') ?? false),
        ];
    }
}
