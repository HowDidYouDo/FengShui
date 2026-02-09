<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->font('Roboto')
            // ------------------
            ->colors([
                'primary' => '#e69137', // Wir können hier auch gleich dein Orange als Primary setzen!
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label(__('App Dashboard'))
                    ->url('/dashboard') // oder route('dashboard')
                    ->icon('heroicon-o-home'),
                // 'logout' ist standardmäßig da
            ])
            // Optional: Sidebar Item ganz oben
            ->navigationItems([
                \Filament\Navigation\NavigationItem::make(__('App Dashboard'))
                    ->url('/dashboard')
                    ->icon('heroicon-o-arrow-left-start-on-rectangle')
                    ->group('System')
                    ->sort(-1),
            ])
            ->plugin(
                SpatieTranslatablePlugin::make()
                    ->persist(true)
                    ->defaultLocales(['de', 'en', 'es', 'fr']),
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
                \App\Http\Middleware\SetLocale::class, // Sprache auch in Filament setzen
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
