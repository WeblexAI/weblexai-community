<?php

namespace App\Filament\Resources\Languages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LanguagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('flag')
                    ->circular()
                    ->getStateUsing(function ($record) {
                        try {
                            $media = $record->getFirstMedia('flag');

                            return $media?->getUrl() ?? url('/images/default-flag.png');
                        } catch (\Exception $e) {
                            return '';
                        }
                    })
                    ->defaultImageUrl('')
                    ->extraImgAttributes(['loading' => 'lazy']),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('country_name')
                    ->searchable(),
                TextColumn::make('iso_2')
                    ->searchable(),
                TextColumn::make('iso_3')
                    ->searchable(),
                ColorColumn::make('color'),
                TextColumn::make('is_active')
                    ->badge(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
