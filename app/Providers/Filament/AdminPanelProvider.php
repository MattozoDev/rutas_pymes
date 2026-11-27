<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use App\Filament\Pages\Inicio;
use App\Filament\Pages\MisEntregas;
use App\Http\Middleware\EstablecerContextoDb;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->brandName('rutapyme')
            ->brandLogo(fn (): HtmlString => new HtmlString(
                '<span style="display:inline-flex;align-items:center;gap:.5rem;color:#f59e0b;font-weight:800;font-size:1.25rem">'
                    .'<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 3 3 6v15l6-3 6 3 6-3V3l-6 3-6-3Z"/><path d="M9 3v15M15 6v15"/></svg>'
                    .'rutapyme</span>'
            ))
            ->colors([
                'primary' => Color::Blue,
                'info' => Color::Sky,
                'success' => Color::Green,
                'warning' => Color::Amber,
                'danger' => Color::Red,
                'gray' => Color::Zinc,
            ])
            ->spa()
            ->darkMode()
            ->sidebarCollapsibleOnDesktop()
            ->globalSearch(false)
            ->navigationGroups(['Gestión', 'Registros', 'Configuración'])
            ->databaseNotifications()
            ->databaseNotificationsPolling('60s')
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): View => view('filament.hooks.leaflet'))
            ->homeUrl(fn (): string => auth()->user()?->esRepartidor() ? MisEntregas::getUrl() : Inicio::getUrl())
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EstablecerContextoDb::class,
            ], isPersistent: true);
    }
}
