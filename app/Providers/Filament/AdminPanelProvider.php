<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\CustomRegister;
use App\Filament\Pages\SetupBusiness;
use App\Models\Tenant;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Http\Middleware\IdentifyTenant;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;



class AdminPanelProvider extends PanelProvider
{
    public function boot()
{
    \Filament\Support\Facades\FilamentView::registerRenderHook(
        'panels::body.start',
        fn (): \Illuminate\Contracts\View\View => view('components.impersonate-banner'),
    );
}
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->registration(CustomRegister::class) 
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->passwordReset() 
            ->profile() 
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->authGuard('web')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
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
                IdentifyTenant::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\CheckUserStatus::class,
            ])
             ->tenantMiddleware([
                // Middleware específico para recursos tenant
            ], isPersistent: true)
            ->navigationGroups([
                \Filament\Navigation\NavigationGroup::make()
            ->label('Settings')
            ->collapsed()
        ]);
        
        
    }
}
