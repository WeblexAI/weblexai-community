<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\LogsAdminActivity;
use App\Settings\MaxmindSettings;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

class ManageMaxmindSettings extends SettingsPage
{
    use LogsAdminActivity;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-map';

    protected static string $settings = MaxmindSettings::class;

    protected static UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'MaxMind';

    protected static ?int $navigationSort = 9;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Configuration')
                ->components([
                    TextInput::make('license_key')
                        ->label('License Key')
                        ->password()
                        ->revealable()
                        ->required(),
                    TextInput::make('user_id')
                        ->label('User ID')
                        ->required(),
                ])->columns(1),
        ]);
    }

    protected function afterSave(): void
    {
        $this->logAdminActivity(
            description: 'Updated MaxMind settings.',
            properties: [
                'settings' => [
                    'class' => MaxmindSettings::class,
                    'label' => 'MaxMind settings',
                ],
            ],
            event: 'updated',
        );
    }
}
