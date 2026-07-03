<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Languages\LanguageResource;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\ProviderCredentials\ProviderCredentialResource;
use App\Filament\Resources\Users\UserResource;
use App\Support\Filament\WeblexLoginBackground;
use Awcodes\QuickCreate\QuickCreatePlugin;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use JibayMcs\FilamentTour\FilamentTourPlugin;
use ShuvroRoy\FilamentSpatieLaravelBackup\FilamentSpatieLaravelBackupPlugin;
use ShuvroRoy\FilamentSpatieLaravelHealth\FilamentSpatieLaravelHealthPlugin;
use Swis\Filament\Backgrounds\FilamentBackgroundsPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('WeblexAI Community Edition')
            ->brandLogo(fn (): HtmlString => new HtmlString(
                '<span class="weblex-admin-brand"><img src="'.asset('images/logo.png').'" alt="" aria-hidden="true"><span>WeblexAI Community Edition</span></span>',
            ))
            ->brandLogoHeight('2.25rem')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->darkMode(false)
            ->multiFactorAuthentication([
                AppAuthentication::make()
                    ->recoverable()
                    ->brandName('WeblexAI Community Edition'),
            ])
            ->profile()
            ->globalSearch(false)
            ->colors([
                'primary' => Color::hex('#2f5d7c'),
                'success' => Color::hex('#287056'),
                'warning' => Color::hex('#7a6a28'),
                'danger' => Color::hex('#b42318'),
            ])
            ->favicon(asset('fav-icon/favicon.ico'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([Dashboard::class])
            ->navigationItems([
                NavigationItem::make('Application logs')
                    ->group('System')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->sort(90)
                    ->visible(fn (): bool => (bool) config('log-viewer.enabled'))
                    ->url(fn (): string => route('admin.logs'), shouldOpenInNewTab: true)
                    ->extraAttributes(['data-tour' => 'admin-logs']),
                NavigationItem::make('User dashboard')
                    ->group('System')
                    ->icon('heroicon-o-squares-2x2')
                    ->sort(91)
                    ->url(fn (): string => route('projects.index'), shouldOpenInNewTab: true)
                    ->extraAttributes(['data-tour' => 'admin-user-dashboard']),
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->plugins([
                FilamentBackgroundsPlugin::make()
                    ->imageProvider(WeblexLoginBackground::make())
                    ->showAttribution(false),
                FilamentTourPlugin::make()
                    ->onlyVisibleOnce(),
                FilamentSpatieLaravelBackupPlugin::make()
                    ->authorize(fn (): bool => auth()->user()?->canAccessPanel($panel) === true)
                    ->navigationGroup('System')
                    ->navigationLabel('Backups')
                    ->navigationIcon('heroicon-o-archive-box')
                    ->navigationSort(70)
                    ->noTimeout(),
                FilamentSpatieLaravelHealthPlugin::make()
                    ->authorize(fn (): bool => auth()->user()?->canAccessPanel($panel) === true)
                    ->navigationGroup('System')
                    ->navigationLabel('Health')
                    ->navigationIcon('heroicon-o-heart')
                    ->navigationSort(80),
                QuickCreatePlugin::make()
                    ->includes([
                        ProjectResource::class,
                        UserResource::class,
                        ProviderCredentialResource::class,
                        LanguageResource::class,
                    ])
                    ->label('Create')
                    ->slideOver()
                    ->sortBy('navigation')
                    ->keyBindings(['command+shift+k', 'ctrl+shift+k']),
            ])
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): HtmlString => new HtmlString(view('filament.admin.login-footer')->render()),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn (): HtmlString => new HtmlString(view('filament.admin.resource-links')->render()),
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([Authenticate::class]);
    }
}
