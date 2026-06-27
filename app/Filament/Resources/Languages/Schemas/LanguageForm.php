<?php

namespace App\Filament\Resources\Languages\Schemas;

use App\Enums\ModelStatus;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LanguageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('country_name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('iso_2')
                    ->required()
                    ->maxLength(2),
                TextInput::make('iso_3')
                    ->required()
                    ->maxLength(3),
                ColorPicker::make('color')
                    ->required(),
                Select::make('is_active')
                    ->options(ModelStatus::class)
                    ->required()
                    ->default(ModelStatus::ACTIVE),
                SpatieMediaLibraryFileUpload::make('flag')
                    ->collection('flag')
                    ->image()
                    ->imageEditor()
                    ->loadStateFromRelationshipsUsing(function ($component, $record) {
                        try {
                            if (! $record) {
                                $component->state([]);

                                return;
                            }

                            $files = $record->getMedia('flag')
                                ->mapWithKeys(fn ($media) => [$media->uuid => $media->uuid])
                                ->toArray();

                            $component->state($files);
                        } catch (\Throwable $e) {
                            $component->state([]);
                        }
                    }),
            ]);
    }
}
