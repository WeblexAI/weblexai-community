<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Enums\ModelStatus;
use App\Filament\Resources\Languages\LanguageResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Project;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class ProjectInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Project details')
                ->persistTabInQueryString()
                ->tabs([
                    Tab::make('Overview')
                        ->schema([
                            Section::make('Project')
                                ->schema([
                                    TextEntry::make('name')->weight('bold')->size('lg'),
                                    TextEntry::make('owner.name')
                                        ->label('Owner')
                                        ->url(fn (Project $record): ?string => $record->owner
                                            ? UserResource::getUrl('view', ['record' => $record->owner])
                                            : null),
                                    TextEntry::make('originalLanguage.name')
                                        ->label('Default language')
                                        ->url(fn (Project $record): ?string => $record->originalLanguage
                                            ? LanguageResource::getUrl('view', ['record' => $record->originalLanguage])
                                            : null),
                                    TextEntry::make('providerCredential.name')
                                        ->label('Translation provider')
                                        ->state(fn (Project $record): string => $record->providerCredential
                                            ? sprintf(
                                                '%s - %s (%s)',
                                                $record->providerCredential->name,
                                                $record->providerCredential->provider_label,
                                                $record->providerCredential->provider_type,
                                            )
                                            : 'Not configured')
                                        ->icon(fn (Project $record): string => $record->providerCredential ? 'heroicon-m-key' : 'heroicon-m-exclamation-triangle')
                                        ->color(fn (Project $record): string => $record->providerCredential ? 'primary' : 'gray'),
                                    TextEntry::make('is_active')
                                        ->label('Status')
                                        ->formatStateUsing(fn (ModelStatus $state): string => $state->getLabel())
                                        ->icon(fn (ModelStatus $state): string => $state === ModelStatus::ACTIVE ? 'heroicon-m-check-circle' : 'heroicon-m-pause-circle')
                                        ->color(fn (ModelStatus $state): string => $state === ModelStatus::ACTIVE ? 'success' : 'gray'),
                                    TextEntry::make('created_at')->dateTime(),
                                    TextEntry::make('updated_at')->dateTime(),
                                ])
                                ->columns(2),
                            Section::make('Operational snapshot')
                                ->schema([
                                    TextEntry::make('pages_count')
                                        ->label('Pages')
                                        ->state(fn (Project $record): int => $record->pages()->count())
                                        ->formatStateUsing(fn (int $state): string => number_format($state))
                                        ->weight('semibold'),
                                    TextEntry::make('translations_count')
                                        ->label('Translations')
                                        ->state(fn (Project $record): int => $record->translations()->count())
                                        ->formatStateUsing(fn (int $state): string => number_format($state))
                                        ->weight('semibold'),
                                    TextEntry::make('languages_count')
                                        ->label('Languages')
                                        ->state(fn (Project $record): int => $record->languages()->count())
                                        ->formatStateUsing(fn (int $state): string => number_format($state))
                                        ->weight('semibold'),
                                    TextEntry::make('collaborators_count')
                                        ->label('Members')
                                        ->state(fn (Project $record): int => $record->collaborators()->count())
                                        ->formatStateUsing(fn (int $state): string => number_format($state))
                                        ->weight('semibold'),
                                    TextEntry::make('origins_count')
                                        ->label('Accepted origins')
                                        ->state(fn (Project $record): int => $record->acceptedOrigins()->count())
                                        ->formatStateUsing(fn (int $state): string => number_format($state))
                                        ->weight('semibold'),
                                    TextEntry::make('requests_count')
                                        ->label('Translation requests')
                                        ->state(fn (Project $record): int => $record->translationRequests()->count())
                                        ->formatStateUsing(fn (int $state): string => number_format($state))
                                        ->weight('semibold'),
                                ])
                                ->columns(3),
                        ]),
                    Tab::make('Integration')
                        ->schema([
                            Section::make('Project API key')
                                ->description('Use this key to initialize WeblexAI on the accepted websites.')
                                ->schema([
                                    TextEntry::make('api_key')
                                        ->label('')
                                        ->copyable(fn (?string $state): bool => filled($state))
                                        ->copyMessage('API key copied')
                                        ->placeholder('Rotate the API key to make it available here.'),
                                ]),
                            Section::make('Accepted origins')
                                ->schema([
                                    RepeatableEntry::make('acceptedOrigins')
                                        ->label('')
                                        ->placeholder('No origin configured. API access is disabled.')
                                        ->contained(false)
                                        ->schema([
                                            TextEntry::make('origin')->copyable(),
                                        ]),
                                ]),
                        ]),
                    Tab::make('Languages')
                        ->schema([
                            RepeatableEntry::make('languages')
                                ->label('')
                                ->placeholder('No target languages configured.')
                                ->contained(false)
                                ->table([
                                    TableColumn::make('Language'),
                                    TableColumn::make('ISO-2'),
                                    TableColumn::make('Status'),
                                ])
                                ->schema([
                                    TextEntry::make('name')
                                        ->url(fn ($record): string => LanguageResource::getUrl('view', ['record' => $record])),
                                    TextEntry::make('iso_2'),
                                    TextEntry::make('is_active')
                                        ->formatStateUsing(fn (ModelStatus $state): string => $state->getLabel())
                                        ->icon(fn (ModelStatus $state): string => $state === ModelStatus::ACTIVE ? 'heroicon-m-check-circle' : 'heroicon-m-pause-circle')
                                        ->color(fn (ModelStatus $state): string => $state === ModelStatus::ACTIVE ? 'success' : 'gray'),
                                ]),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
