<?php

namespace App\Filament\Resources\ProviderCredentials;

use App\Enums\TranslationProvider;
use App\Filament\Resources\ProviderCredentials\Pages\CreateProviderCredential;
use App\Filament\Resources\ProviderCredentials\Pages\EditProviderCredential;
use App\Filament\Resources\ProviderCredentials\Pages\ListProviderCredentials;
use App\Models\ProviderCredential;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProviderCredentialResource extends Resource
{
    protected static ?string $model = ProviderCredential::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $navigationLabel = 'Provider Credentials';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Provider')
                ->columnSpanFull()
                ->columns(['default' => 1, 'md' => 2])
                ->description('Store the provider keys WeblexAI uses for automatic translations. Secrets are encrypted and are never exposed through the browser SDK.')
                ->schema([
                    TextInput::make('name')
                        ->label('Credential name')
                        ->autocomplete('off')
                        ->extraInputAttributes(['data-1p-ignore' => 'true', 'data-lpignore' => 'true', 'spellcheck' => 'false'])
                        ->helperText('Use a name administrators can recognize when assigning this credential to projects.')
                        ->required()
                        ->maxLength(255),
                    Select::make('provider')
                        ->options(collect(TranslationProvider::cases())->mapWithKeys(fn (TranslationProvider $provider): array => [
                            $provider->value => $provider->getLabel().' ('.$provider->type()->value.')',
                        ])->all())
                        ->required()
                        ->live(),
                    TextInput::make('api_key')
                        ->label('API key')
                        ->autocomplete('new-password')
                        ->extraInputAttributes(['data-1p-ignore' => 'true', 'data-lpignore' => 'true', 'spellcheck' => 'false'])
                        ->password()
                        ->revealable()
                        ->helperText('Leave this blank when editing to keep the existing key.')
                        ->afterStateHydrated(fn (TextInput $component) => $component->state(null))
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->required(fn (Get $get, ?ProviderCredential $record): bool => $get('provider') !== TranslationProvider::GOOGLE->value && blank($record?->api_key)),
                    TextInput::make('model')
                        ->helperText('Leave blank to use WeblexAI recommended model for this provider.')
                        ->visible(fn (Get $get): bool => $get('provider') !== TranslationProvider::GOOGLE->value),
                    TextInput::make('google_project_id')
                        ->helperText('The Google Cloud project that owns the Translation API credential.')
                        ->required(fn (Get $get): bool => $get('provider') === TranslationProvider::GOOGLE->value)
                        ->visible(fn (Get $get): bool => $get('provider') === TranslationProvider::GOOGLE->value),
                    Textarea::make('service_account')
                        ->label('Service account JSON')
                        ->helperText('Paste the full JSON credential. Leave blank when editing to keep the existing service account.')
                        ->rows(10)
                        ->afterStateHydrated(fn (Textarea $component) => $component->state(null))
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->required(fn (Get $get, ?ProviderCredential $record): bool => $get('provider') === TranslationProvider::GOOGLE->value && blank($record?->service_account))
                        ->visible(fn (Get $get): bool => $get('provider') === TranslationProvider::GOOGLE->value)
                        ->columnSpanFull(),
                    Select::make('is_active')
                        ->options([1 => 'Active', 0 => 'Inactive'])
                        ->default(1)
                        ->required(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('provider_label')->label('Provider')->badge(),
                TextColumn::make('provider_type')->label('Type')->badge(),
                TextColumn::make('model')->placeholder('Provider default'),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->disabled(fn (ProviderCredential $record): bool => $record->projects()->exists()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProviderCredentials::route('/'),
            'create' => CreateProviderCredential::route('/create'),
            'edit' => EditProviderCredential::route('/{record}/edit'),
        ];
    }
}
