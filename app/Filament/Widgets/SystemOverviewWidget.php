<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use App\Models\ProviderCredential;
use App\Models\Translation;
use App\Models\TranslationRequest;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SystemOverviewWidget extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 10;

    protected ?string $heading = 'System overview';

    protected ?string $description = 'Operational snapshot for this WeblexAI installation.';

    protected function getStats(): array
    {
        $projects = Project::query()->count();
        $integratedProjects = Project::query()
            ->has('acceptedOrigins')
            ->has('languages')
            ->has('translations')
            ->count();
        $readinessGaps = Project::query()
            ->where(function ($query): void {
                $query
                    ->whereNull('provider_credential_id')
                    ->orWhereDoesntHave('acceptedOrigins')
                    ->orWhereDoesntHave('languages');
            })
            ->count();
        $requestsToday = TranslationRequest::query()
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
        $reviewedTranslations = Translation::query()
            ->where('is_original', false)
            ->where('is_reviewed', true)
            ->count();
        $translatedStrings = Translation::query()
            ->where('is_original', false)
            ->count();
        $reviewRate = $translatedStrings > 0
            ? round(($reviewedTranslations / $translatedStrings) * 100)
            : 0;

        return [
            Stat::make('Projects', number_format($projects))
                ->description(number_format($integratedProjects).' integrated')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($integratedProjects > 0 ? 'success' : 'gray'),
            Stat::make('Setup gaps', number_format($readinessGaps))
                ->description($readinessGaps > 0 ? 'Projects missing provider, origins, or languages' : 'All projects are ready')
                ->descriptionIcon($readinessGaps > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($readinessGaps > 0 ? 'warning' : 'success'),
            Stat::make('Requests today', number_format($requestsToday))
                ->description('Browser translation API calls')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($requestsToday > 0 ? 'primary' : 'gray'),
            Stat::make('Review rate', $reviewRate.'%')
                ->description(number_format($reviewedTranslations).' of '.number_format($translatedStrings).' translations reviewed')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color($reviewRate >= 80 ? 'success' : ($translatedStrings > 0 ? 'warning' : 'gray')),
            Stat::make('Provider keys', number_format(ProviderCredential::query()->where('is_active', true)->count()))
                ->description('Active translation credentials')
                ->descriptionIcon('heroicon-m-key')
                ->color('primary'),
            Stat::make('Users', number_format(User::query()->count()))
                ->description('Administrators and project members')
                ->descriptionIcon('heroicon-m-users')
                ->color('gray'),
        ];
    }
}
