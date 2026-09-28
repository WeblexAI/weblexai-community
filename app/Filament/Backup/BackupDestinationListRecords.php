<?php

namespace App\Filament\Backup;

use App\Models\BackupArchive;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use RuntimeException;
use ShuvroRoy\FilamentSpatieLaravelBackup\Enums\BackupType;
use ShuvroRoy\FilamentSpatieLaravelBackup\FilamentSpatieLaravelBackup;
use ShuvroRoy\FilamentSpatieLaravelBackup\FilamentSpatieLaravelBackupPlugin;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupDestinationListRecords extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    /**
     * @var array<int|string, array<string, string>|string>
     */
    protected $queryString = [
        'tableSortColumn',
        'tableSortDirection',
        'tableSearchQuery' => ['except' => ''],
    ];

    public function render(): View
    {
        return app(ViewFactory::class)->make('filament.components.backup-destination-list-records');
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(
                function (
                    ?string $sortColumn,
                    ?string $sortDirection,
                    ?string $search,
                    ?array $filters,
                    int|string $page,
                    int|string|null $recordsPerPage,
                ): LengthAwarePaginator {
                    $plugin = FilamentSpatieLaravelBackupPlugin::get();
                    $configuredDisks = FilamentSpatieLaravelBackup::getDisks();
                    $filteredType = data_get($filters, 'type.value');
                    $data = [];

                    foreach ($configuredDisks as $disk) {
                        $data = array_merge(
                            $data,
                            FilamentSpatieLaravelBackup::getBackupDestinationData(
                                $disk,
                                $plugin->getCacheDuration(),
                            ),
                        );
                    }

                    $paths = collect($data)->pluck('path')->all();
                    $passwords = BackupArchive::query()
                        ->whereIn('path', $paths)
                        ->get(['path', 'archive_password'])
                        ->mapWithKeys(fn (BackupArchive $archive): array => [
                            $archive->path => $archive->archive_password,
                        ]);

                    $data = collect($data)
                        ->map(fn (array $record): array => [
                            ...$record,
                            'password' => $passwords->get($record['path'], 'Not recorded'),
                        ])
                        ->when(
                            filled($filteredType),
                            fn (Collection $data): Collection => $data->where('type', $filteredType),
                        )
                        ->sortByDesc('date');

                    $backupLimit = $plugin->getBackupLimit();

                    if ($backupLimit !== null) {
                        $data = $data->take($backupLimit);
                    }

                    if ($sortColumn !== null && $sortColumn !== '') {
                        $data = $data->sortBy(
                            $sortColumn,
                            SORT_NATURAL,
                            $sortDirection === 'desc',
                        );
                    }

                    if ($search !== null && $search !== '') {
                        $data = $data->filter(
                            fn (array $record): bool => Str::contains(
                                Str::lower($record['path'].$record['date'].$record['password']),
                                Str::lower($search),
                            ),
                        );
                    }

                    $data = $data->values();

                    $page = max((int) $page, 1);
                    $recordsPerPage = $recordsPerPage === 'all'
                        ? max($data->count(), 1)
                        : max((int) ($recordsPerPage ?? 10), 1);

                    return new LengthAwarePaginator(
                        items: $data->forPage($page, $recordsPerPage),
                        total: $data->count(),
                        perPage: $recordsPerPage,
                        currentPage: $page,
                    );
                },
            )
            ->columns([
                TextColumn::make('path')
                    ->label('Backup')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('password')
                    ->label('Archive password')
                    ->copyable(),
                TextColumn::make('date')
                    ->label('Date')
                    ->dateTime()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('size')
                    ->label('Size')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Backup contents')
                    ->options([
                        BackupType::ONLY_DATABASE->value => 'Database only',
                        BackupType::ONLY_FILES->value => 'Files only',
                        BackupType::DATABASE_AND_FILES->value => 'Database and files',
                    ]),
            ])
            ->deferFilters(false)
            ->paginationPageOptions([10, 25, 50])
            ->recordActions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(auth()->user()?->can('download-backup') ?? false)
                    ->action($this->downloadBackup(...)),
                Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->visible(auth()->user()?->can('delete-backup') ?? false)
                    ->requiresConfirmation()
                    ->color('danger')
                    ->modalIcon('heroicon-o-trash')
                    ->action($this->deleteBackup(...)),
            ]);
    }

    /** @param array{disk: string, path: string, ...} $record */
    protected function downloadBackup(array $record): StreamedResponse
    {
        return Storage::disk($record['disk'])->download($record['path']);
    }

    /** @param array{disk: string, path: string, ...} $record */
    protected function deleteBackup(array $record): void
    {
        if (! Storage::disk($record['disk'])->delete($record['path'])) {
            throw new RuntimeException(sprintf(
                'The backup [%s] could not be deleted from disk [%s].',
                $record['path'],
                $record['disk'],
            ));
        }

        BackupArchive::query()->where('path', $record['path'])->delete();

        FilamentSpatieLaravelBackup::clearBackupDestinationCache(
            $record['disk'],
            config()->string('backup.backup.name'),
        );
        $this->resetTable();

        Notification::make()
            ->title('Backup deleted')
            ->success()
            ->send();
    }

    #[Computed]
    public function interval(): ?string
    {
        return FilamentSpatieLaravelBackupPlugin::get()->getPollingInterval();
    }
}
