<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\LogsAdminActivity;
use App\Settings\ErrorReportingSettings;
use BackedEnum;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

class ManageErrorReportingSettings extends SettingsPage
{
    use LogsAdminActivity;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-bug-ant';

    protected static string $settings = ErrorReportingSettings::class;

    protected static UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Diagnostics';

    protected static ?int $navigationSort = 10;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Error reporting')
                ->components([
                    Toggle::make('enabled')
                        ->label('Send diagnostic error reports')
                        ->helperText('When enabled, sanitized error reports are sent to WeblexAI for troubleshooting. Request bodies, credentials, cookies, headers, and user data are excluded.'),
                ]),
        ]);
    }

    protected function afterSave(): void
    {
        $this->logAdminActivity(
            description: 'Updated error reporting settings.',
            properties: [
                'settings' => [
                    'class' => ErrorReportingSettings::class,
                    'label' => 'Error reporting settings',
                ],
            ],
            event: 'updated',
        );
    }
}
