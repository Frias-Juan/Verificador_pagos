<?php
// app/Providers/Filament/EmployeePanelProvider.php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Pages\VerifyPaymentPage; // Importar la página

class EmployeePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('employee')
            // ⚠️ RUTA DEL PORTAL: Aquí se accederá el empleado (ej: /verificacion-pagos)
            ->path('verificacion-pagos') 
            ->login() // Habilita el formulario de login para esta ruta
            
            // ⚠️ 1. DISEÑO MÍNIMO
            ->brandName('Portal de Verificación') // Nombre que verán en el login
            ->colors([
                'primary' => Color::Green,
            ])
            
            // ⚠️ 2. NINGÚN RECURSO, WIDGETS O DASHBOARD
            ->discoverResources(in: app_path('Filament/Employee/Resources'), for: 'App\\Filament\\Employee\\Resources') // Se puede dejar vacío
            ->discoverPages(in: app_path('Filament/Employee/Pages'), for: 'App\\Filament\\Employee\\Pages') // Se puede dejar vacío
            ->discoverWidgets(in: app_path('Filament/Employee/Widgets'), for: 'App\\Filament\\Employee\\Widgets') // Se puede dejar vacío
            ->hasTenancy(false) // Deshabilitar tenancy si no lo necesitas aquí
            ->globalSearch(false)
            ->sidebarCollapsibleOnDesktop()
            ->viteTheme('resources/css/filament/employee/theme.css')
            
            // ⚠️ 3. LISTAR SOLO LA PÁGINA DE VERIFICACIÓN
            ->pages([
                // Si solo quieres que vea esta página, la pones como la única página
                VerifyPaymentPage::class,
            ])
            
            // ⚠️ 4. SOBRESCRIBIR LA NAVEGACIÓN
            ->navigation(function (\Filament\Navigation\NavigationBuilder $builder): \Filament\Navigation\NavigationBuilder {
                return $builder->items([
                    // Como solo hay una página, eliminamos el menú lateral para que solo sea la página
                ]);
            })
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
            ]);
    }
}