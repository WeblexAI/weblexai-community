<?php

namespace App\Filament\Resources\Projects\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('owner.name')->label('Owner')->searchable(),
                TextColumn::make('originalLanguage.name')->label('Default language')->searchable(),
                TextColumn::make('providerCredential.provider_label')
                    ->label('Provider')
                    ->placeholder('Not configured')
                    ->badge(),
                TextColumn::make('accepted_origins_count')
                    ->counts('acceptedOrigins')
                    ->label('Origins'),
                TextColumn::make('collaborators_count')
                    ->counts('collaborators')
                    ->label('Members'),
                TextColumn::make('is_active')->label('Status')->badge(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
