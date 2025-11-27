<?php

namespace App\Providers\Filament;

use Archilex\AdvancedTables\Plugin\AdvancedTablesPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Size;
use Filament\Widgets;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Kenepa\Banner\BannerPlugin;
use pxlrbt\FilamentSpotlight\SpotlightPlugin;

class DashboardPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('dashboard')
            ->path('dashboard')
            ->colors([
                'primary' => Color::Slate,
            ])
            ->discoverResources(in: app_path('Filament/Dashboard/Resources'), for: 'App\\Filament\\Dashboard\\Resources')
            ->discoverPages(in: app_path('Filament/Dashboard/Pages'), for: 'App\\Filament\\Dashboard\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->homeUrl('/')
            ->plugins([
                AdvancedTablesPlugin::make()
                    ->persistActiveViewInSession()
                    ->favoritesBarDivider()
                    ->favoritesBarSize(Size::Small)
                    ->favoritesBarDefaultView(false)
                    ->presetViewsManageable(false)
                    ->resourceEnabled(false),
                SpotlightPlugin::make(),
                BannerPlugin::make()
                    ->disableBannerManager(),
            ])
            ->discoverWidgets(in: app_path('Filament/Dashboard/Widgets'), for: 'App\\Filament\\Dashboard\\Widgets')
            ->widgets([
                // Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('Profile')
                    ->url('/user/profile')
                    ->icon('heroicon-s-cog'),
            ])
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
            ->authMiddleware([
                Authenticate::class,
                EnsureEmailIsVerified::class,
            ])
            ->databaseNotifications()
            ->brandLogo(asset('img/logo.svg'))
            ->brandLogoHeight('3rem')
            ->darkMode(false)
            ->sidebarCollapsibleOnDesktop()
            ->viteTheme('resources/css/filament/dashboard/theme.css')
            ->renderHook(
                'panels::body.end',
                fn (): string => view('components.tawk-chat')
            )
            ->renderHook(
                'panels::body.start',
                fn (): string => view('components.dev-banner')->render()
            );
    }
}
