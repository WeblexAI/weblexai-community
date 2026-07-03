<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use JibayMcs\FilamentTour\Tour\HasTour;
use JibayMcs\FilamentTour\Tour\Step;
use JibayMcs\FilamentTour\Tour\Tour;

class Dashboard extends BaseDashboard
{
    use HasTour;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('startTour')
                ->label('Start tour')
                ->icon('heroicon-o-map')
                ->color('gray')
                ->alpineClickHandler("Livewire.dispatch('filament-tour::open-tour', 'admin_dashboard')")
                ->livewireClickHandlerEnabled(false)
                ->extraAttributes(['data-tour' => 'admin-start-tour']),
        ];
    }

    public function tours(): array
    {
        return [
            Tour::make('admin_dashboard')
                ->route('/admin')
                ->colors('rgba(15, 23, 42, 0.48)', 'rgba(0, 0, 0, 0.72)')
                ->nextButtonLabel('Next')
                ->previousButtonLabel('Back')
                ->doneButtonLabel('Finish')
                ->steps(
                    Step::make()
                        ->title('WeblexAI administration')
                        ->description('This dashboard is your control room for projects, provider keys, users, backups, health, and operational logs.')
                        ->icon('heroicon-o-home')
                        ->iconColor('primary'),
                    Step::make('[data-tour="admin-start-tour"]')
                        ->title('Replay this walkthrough')
                        ->description('Use this button whenever you want to run the admin tour again.')
                        ->icon('heroicon-o-map')
                        ->iconColor('gray'),
                    Step::make('.fi-wi-stats-overview-widget')
                        ->title('System snapshot')
                        ->description('These cards show setup gaps, translation traffic, review coverage, active provider keys, and users.')
                        ->icon('heroicon-o-chart-bar')
                        ->iconColor('primary'),
                    Step::make('.fi-sidebar-nav')
                        ->title('Main navigation')
                        ->description('Projects, provider credentials, languages, users, activity logs, backups, health checks, updates, and reset tools live here.')
                        ->icon('heroicon-o-bars-3')
                        ->iconColor('gray'),
                    Step::make('[data-tour="admin-logs"]')
                        ->title('Logs and diagnostics')
                        ->description('Open application logs in a new tab when you need to inspect failures from the admin panel.')
                        ->icon('heroicon-o-document-magnifying-glass')
                        ->iconColor('warning'),
                    Step::make('[data-tour="admin-user-dashboard"]')
                        ->title('Switch to the user dashboard')
                        ->description('Use this link to review the project-facing experience without leaving the admin session.')
                        ->icon('heroicon-o-squares-2x2')
                        ->iconColor('primary'),
                ),
        ];
    }
}
