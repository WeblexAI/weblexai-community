<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\LogsAdminActivity;
use App\Settings\CacheSettings;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

class ManageCacheSettings extends SettingsPage
{
    use LogsAdminActivity;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cpu-chip';

    protected static string $settings = CacheSettings::class;

    protected static UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Caching';

    protected static ?int $navigationSort = 3;

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Time To Live (Days)')
                    ->components([
                        TextInput::make('translation_ttl')
                            ->label('Translation Cache TTL')
                            ->numeric()
                            ->default(30)
                            ->helperText('Duration in days for translation caching.'),

                        TextInput::make('project_config_ttl')
                            ->label('Project Config TTL')
                            ->numeric()
                            ->default(3600)
                            ->helperText('Duration in days for project configuration caching.'),

                        TextInput::make('glossary_ttl')
                            ->label('Glossary TTL')
                            ->numeric()
                            ->default(3600)
                            ->helperText('Duration in days for project glossary caching.'),
                    ])
                    ->columns(1),
            ]);
    }

    protected function afterSave(): void
    {
        $this->logAdminActivity(
            description: 'Updated cache settings.',
            properties: [
                'settings' => [
                    'class' => CacheSettings::class,
                    'label' => 'Cache settings',
                ],
            ],
            event: 'updated',
        );
    }
}
