<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Enums\ModelStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            Select::make('original_language_id')
                ->relationship('originalLanguage', 'name')
                ->required()
                ->searchable()
                ->preload(),
            Select::make('provider_credential_id')
                ->label('Translation provider')
                ->relationship(
                    'providerCredential',
                    'name',
                    modifyQueryUsing: fn ($query) => $query->where('is_active', true),
                )
                ->getOptionLabelFromRecordUsing(fn ($record): string => sprintf(
                    '%s - %s (%s)',
                    $record->name,
                    $record->provider_label,
                    $record->provider_type,
                ))
                ->searchable()
                ->preload()
                ->nullable()
                ->helperText('Translations remain disabled until a provider credential is assigned.'),
            Select::make('is_active')
                ->options(ModelStatus::class)
                ->required()
                ->default(ModelStatus::ACTIVE),
        ]);
    }
}
