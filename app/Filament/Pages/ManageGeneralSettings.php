<?php

namespace App\Filament\Pages;

use App\Enums\Currency;
use App\Filament\Concerns\LogsAdminActivity;
use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use UnitEnum;

class ManageGeneralSettings extends SettingsPage
{
    use LogsAdminActivity;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $settings = GeneralSettings::class;

    protected static UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'General';

    protected static ?int $navigationSort = 1;

    public function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('URLs')
                ->components([
                    TextInput::make('marketing_url')
                        ->label('Marketing Site URL')
                        ->url()
                        ->required(),

                    TextInput::make('dashboard_url')
                        ->label('Dashboard URL')
                        ->url()
                        ->required(),

                    TextInput::make('cdn_url')
                        ->label('CDN URL')
                        ->url()
                        ->required(),
                ])
                ->columns(1),

            Section::make('Currency')
                ->components([
                    Select::make('app_currency')
                        ->label('App Currency')
                        ->options(collect(Currency::cases())->mapWithKeys(fn ($c) => [$c->value => Str::replace('_', ' ', $c->name)]))
                        ->required(),
                ])
                ->columns(1),
        ]);
    }

    protected function afterSave(): void
    {
        $this->logAdminActivity(
            description: 'Updated general settings.',
            properties: [
                'settings' => [
                    'class' => GeneralSettings::class,
                    'label' => 'General settings',
                ],
            ],
            event: 'updated',
        );
    }
}
