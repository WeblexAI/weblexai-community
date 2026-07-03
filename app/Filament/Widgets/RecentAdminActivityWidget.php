<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\AdminActivities\AdminActivityResource;
use App\Models\ActivityLog;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Str;

class RecentAdminActivityWidget extends TableWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 30;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent admin activity')
            ->description('Latest configuration and administrative changes.')
            ->query(
                ActivityLog::query()
                    ->where('log_name', 'admin')
                    ->with(['causer', 'subject'])
                    ->latest()
            )
            ->paginated(false)
            ->emptyStateHeading('No admin activity has been recorded yet.')
            ->columns([
                TextColumn::make('description')
                    ->label('Activity')
                    ->wrap()
                    ->limit(90),
                TextColumn::make('event')
                    ->label('Event')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? Str::headline($state) : 'Logged')
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('causer_label')
                    ->label('Admin')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('target_type_label')
                    ->label('Type')
                    ->badge()
                    ->color('primary')
                    ->placeholder('General'),
                TextColumn::make('created_at')
                    ->label('When')
                    ->since()
                    ->sortable()
                    ->tooltip(fn (ActivityLog $record): ?string => $record->created_at?->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s T')),
            ])
            ->recordUrl(fn (): string => AdminActivityResource::getUrl('index'));
    }
}
