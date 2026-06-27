<?php

namespace App\Filament\Pages;

use App\Support\Installation\ApplicationResetter;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use UnitEnum;

class ResetApplication extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static UnitEnum|string|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Reset application';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.reset-application';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resetApplication')
                ->label('Reset application')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->modalHeading('Reset WeblexAI')
                ->modalDescription('This permanently deletes all application data and local uploads. External storage objects and infrastructure credentials are not deleted.')
                ->modalSubmitActionLabel('Reset application')
                ->form([
                    TextInput::make('current_password')
                        ->label('Current password')
                        ->password()
                        ->revealable()
                        ->currentPassword()
                        ->required(),
                    TextInput::make('confirmation')
                        ->label('Type RESET WEBLEXAI to confirm')
                        ->rules([Rule::in(['RESET WEBLEXAI'])])
                        ->validationMessages([
                            'in' => 'Type RESET WEBLEXAI exactly as shown.',
                        ])
                        ->required(),
                ])
                ->action(function (array $data, ApplicationResetter $resetter): void {
                    $administrator = Auth::user();

                    $resetter->reset(
                        administratorId: (int) $administrator->getAuthIdentifier(),
                        administratorEmail: (string) $administrator->email,
                    );

                    Auth::logout();

                    if (request()->hasSession()) {
                        request()->session()->invalidate();
                        request()->session()->regenerateToken();
                    }

                    $this->redirect(route('install.show'), navigate: false);
                }),
        ];
    }
}
