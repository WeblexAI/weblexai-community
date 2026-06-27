<?php

namespace App\Filament\Resources\Users\Schemas;

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
                    TextEntry::make('roles.name')->label('System role')->badge(),
                    TextEntry::make('is_active')->label('Status')->badge(),
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
                            TextEntry::make('languages_count')->badge(),
                            TextEntry::make('translations_count')->badge(),
                            TextEntry::make('accepted_origins_count')->badge(),
                            TextEntry::make('is_active')->badge(),
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
                            TextEntry::make('is_active')->badge(),
                        ]),
                ]),
        ]);
    }
}
