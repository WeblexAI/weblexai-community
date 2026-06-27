<?php

namespace App\Filament\Resources\Languages\Schemas;

use App\Filament\Resources\Languages\LanguageResource;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Glossary;
use App\Models\Language;
use App\Models\Project;
use Filament\Infolists\Components\ColorEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LanguageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Language Overview')
                    ->schema([
                        ImageEntry::make('flag')
                            ->circular()
                            ->getStateUsing(function ($record) {
                                try {
                                    $media = $record->getFirstMedia('flag');

                                    return $media?->getUrl() ?? '';
                                } catch (\Exception $e) {
                                    return '';
                                }
                            })
                            ->defaultImageUrl('')
                            ->extraImgAttributes(['loading' => 'lazy']),
                        TextEntry::make('name')
                            ->weight('bold')
                            ->size('lg'),
                        TextEntry::make('country_name'),
                        TextEntry::make('iso_2'),
                        TextEntry::make('iso_3'),
                        ColorEntry::make('color'),
                        TextEntry::make('is_active')
                            ->label('Status')
                            ->badge(),
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ])->columns(2),

                Section::make('Usage Snapshot')
                    ->schema([
                        TextEntry::make('default_projects_count')
                            ->label('Default Language For')
                            ->state(fn (Language $record): int => Project::query()->where('original_language_id', $record->id)->count())
                            ->badge(),
                        TextEntry::make('attached_projects_count')
                            ->label('Attached To Projects')
                            ->state(fn (Language $record): int => Project::query()
                                ->whereHas('languages', fn ($query) => $query->whereKey($record->id))
                                ->count())
                            ->badge(),
                        TextEntry::make('target_translations_count')
                            ->label('Target Translations')
                            ->state(fn (Language $record): int => $record->translations()->count())
                            ->badge(),
                        TextEntry::make('glossary_count')
                            ->label('Glossaries')
                            ->state(fn (Language $record): int => Glossary::query()
                                ->whereHas('languages', fn ($query) => $query->whereKey($record->id))
                                ->count())
                            ->badge(),
                    ])->columns(4),

                Section::make('Projects Using This As Default Language')
                    ->schema([
                        RepeatableEntry::make('default_language_projects')
                            ->label('')
                            ->state(fn (Language $record) => Project::query()
                                ->where('original_language_id', $record->id)
                                ->with('user')
                                ->withCount(['translations', 'languages'])
                                ->latest()
                                ->get())
                            ->placeholder('No projects use this language as their default language.')
                            ->contained(false)
                            ->table([
                                TableColumn::make('Project'),
                                TableColumn::make('Owner'),
                                TableColumn::make('Languages'),
                                TableColumn::make('Translations'),
                                TableColumn::make('Status'),
                            ])
                            ->schema([
                                TextEntry::make('name')
                                    ->url(fn ($record): string => ProjectResource::getUrl('view', ['record' => $record])),
                                TextEntry::make('user.name')
                                    ->url(fn ($record): ?string => $record->user ? UserResource::getUrl('view', ['record' => $record->user]) : null),
                                TextEntry::make('languages_count')
                                    ->badge(),
                                TextEntry::make('translations_count')
                                    ->badge(),
                                TextEntry::make('is_active')
                                    ->badge(),
                            ]),
                    ])
                    ->columnSpanFull(),

                Section::make('Projects With This Language Attached')
                    ->schema([
                        RepeatableEntry::make('attached_language_projects')
                            ->label('')
                            ->state(fn (Language $record) => Project::query()
                                ->whereHas('languages', fn ($query) => $query->whereKey($record->id))
                                ->with(['user', 'originalLanguage'])
                                ->withCount(['translations'])
                                ->latest()
                                ->get())
                            ->placeholder('No projects currently have this language attached.')
                            ->contained(false)
                            ->table([
                                TableColumn::make('Project'),
                                TableColumn::make('Owner'),
                                TableColumn::make('Default Language'),
                                TableColumn::make('Translations'),
                                TableColumn::make('Status'),
                            ])
                            ->schema([
                                TextEntry::make('name')
                                    ->url(fn ($record): string => ProjectResource::getUrl('view', ['record' => $record])),
                                TextEntry::make('user.name')
                                    ->url(fn ($record): ?string => $record->user ? UserResource::getUrl('view', ['record' => $record->user]) : null),
                                TextEntry::make('originalLanguage.name')
                                    ->url(fn ($record): ?string => $record->originalLanguage ? LanguageResource::getUrl('view', ['record' => $record->originalLanguage]) : null),
                                TextEntry::make('translations_count')
                                    ->badge(),
                                TextEntry::make('is_active')
                                    ->badge(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
