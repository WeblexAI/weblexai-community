<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\ModelStatus;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\User;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('User')
                ->schema([
                    TextEntry::make('name')->weight('bold')->size('lg'),
                    TextEntry::make('email')->copyable(),
                    TextEntry::make('roles.name')
                        ->label('System role')
                        ->separator(', ')
                        ->weight('semibold'),
                    TextEntry::make('is_active')
                        ->label('Status')
                        ->formatStateUsing(fn (ModelStatus $state): string => $state->getLabel())
                        ->icon(fn (ModelStatus $state): string => $state === ModelStatus::ACTIVE ? 'heroicon-m-check-circle' : 'heroicon-m-pause-circle')
                        ->color(fn (ModelStatus $state): string => $state === ModelStatus::ACTIVE ? 'success' : 'gray'),
                    IconEntry::make('force_password_change')->boolean(),
                    TextEntry::make('created_at')->dateTime(),
                ])
                ->columns(2),
            Section::make('Owned projects')
                ->schema([
                    RepeatableEntry::make('projects')
                        ->label('')
                        ->state(fn (User $record) => $record->projects()
                            ->withCount(['translations', 'languages', 'acceptedOrigins'])
                            ->latest()
                            ->get())
                        ->placeholder('No owned projects.')
                        ->contained(false)
                        ->table([
                            TableColumn::make('Project'),
                            TableColumn::make('Languages'),
                            TableColumn::make('Translations'),
                            TableColumn::make('Origins'),
                            TableColumn::make('Status'),
                        ])
                        ->schema([
                            TextEntry::make('name')
                                ->url(fn ($record): string => ProjectResource::getUrl('view', ['record' => $record])),
                            TextEntry::make('languages_count')
                                ->formatStateUsing(fn (int $state): string => number_format($state))
                                ->weight('semibold'),
                            TextEntry::make('translations_count')
                                ->formatStateUsing(fn (int $state): string => number_format($state))
                                ->weight('semibold'),
                            TextEntry::make('accepted_origins_count')
                                ->formatStateUsing(fn (int $state): string => number_format($state))
                                ->weight('semibold'),
                            TextEntry::make('is_active')
                                ->formatStateUsing(fn (ModelStatus $state): string => $state->getLabel())
                                ->icon(fn (ModelStatus $state): string => $state === ModelStatus::ACTIVE ? 'heroicon-m-check-circle' : 'heroicon-m-pause-circle')
                                ->color(fn (ModelStatus $state): string => $state === ModelStatus::ACTIVE ? 'success' : 'gray'),
                        ]),
                ]),
            Section::make('Project memberships')
                ->schema([
                    RepeatableEntry::make('collaborated_projects')
                        ->label('')
                        ->state(fn (User $record) => $record->collaboratedProjects()
                            ->with('owner')
                            ->latest()
                            ->get())
                        ->placeholder('No project memberships.')
                        ->contained(false)
                        ->table([
                            TableColumn::make('Project'),
                            TableColumn::make('Owner'),
                            TableColumn::make('Role'),
                            TableColumn::make('Status'),
                        ])
                        ->schema([
                            TextEntry::make('name')
                                ->url(fn ($record): string => ProjectResource::getUrl('view', ['record' => $record])),
                            TextEntry::make('owner.name'),
                            TextEntry::make('pivot.role')->badge(),
                            TextEntry::make('is_active')
                                ->formatStateUsing(fn (ModelStatus $state): string => $state->getLabel())
                                ->icon(fn (ModelStatus $state): string => $state === ModelStatus::ACTIVE ? 'heroicon-m-check-circle' : 'heroicon-m-pause-circle')
                                ->color(fn (ModelStatus $state): string => $state === ModelStatus::ACTIVE ? 'success' : 'gray'),
                        ]),
                ]),
        ]);
    }
}
