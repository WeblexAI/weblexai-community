<?php

namespace App\Filament\Backup;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use ShuvroRoy\FilamentSpatieLaravelBackup\FilamentSpatieLaravelBackup;
use ShuvroRoy\FilamentSpatieLaravelBackup\FilamentSpatieLaravelBackupPlugin;

class BackupDestinationStatusListRecords extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public function render(): View
    {
        return app(ViewFactory::class)->make('filament.components.backup-destination-status-list-records');
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(
                fn (): array => FilamentSpatieLaravelBackup::getBackupDestinationStatusData(
                    FilamentSpatieLaravelBackupPlugin::get()->getCacheDuration(),
                ),
            )
            ->columns([
                TextColumn::make('name')->label('Name'),
                IconColumn::make('healthy')->label('Healthy')->boolean(),
                TextColumn::make('amount')->label('Backups'),
                TextColumn::make('newest')->label('Newest'),
                TextColumn::make('usedStorage')->label('Used storage')->badge(),
            ]);
    }

    #[Computed]
    public function interval(): ?string
    {
        return FilamentSpatieLaravelBackupPlugin::get()->getPollingInterval();
    }
}
