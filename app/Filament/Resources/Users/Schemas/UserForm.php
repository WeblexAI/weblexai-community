<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\ModelStatus;
use App\Enums\UserRole;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            TextInput::make('password')
                ->password()
                ->required(fn (string $operation): bool => $operation === 'create')
                ->rule(Password::defaults())
                ->dehydrated(fn ($state): bool => filled($state)),
            Select::make('system_role')
                ->label('Role')
                ->options(fn (): array => Role::query()
                    ->orderBy('name')
                    ->get()
                    ->mapWithKeys(fn (Role $role): array => [$role->getKey() => ucfirst($role->name)])
                    ->all())
                ->required()
                ->default(fn (): int => Role::findByName(UserRole::USER->value)->getKey())
                ->afterStateHydrated(function (Select $component, ?User $record): void {
                    $component->state($record?->roles()->value('roles.id') ?? Role::findByName(UserRole::USER->value)->getKey());
                })
                ->native(false)
                ->selectablePlaceholder(false)
                ->preload(),
            Select::make('is_active')
                ->options(ModelStatus::class)
                ->required()
                ->default(ModelStatus::ACTIVE),
        ]);
    }
}
