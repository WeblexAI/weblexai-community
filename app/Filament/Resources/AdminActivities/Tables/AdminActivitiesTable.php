<?php

namespace App\Filament\Resources\AdminActivities\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class AdminActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100])
            ->emptyStateHeading('No admin activity has been recorded yet.')
            ->columns([
                TextColumn::make('description')
                    ->label('Activity')
                    ->searchable()
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
                TextColumn::make('target_label')
                    ->label('Target')
                    ->wrap()
                    ->placeholder('System settings')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('When')
                    ->since()
                    ->sortable()
                    ->tooltip(fn ($record): ?string => $record->created_at?->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s T')),
            ])
            ->filters([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
