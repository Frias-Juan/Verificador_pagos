<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        if ($request->routeIs('filament.admin.auth.logout')) {
            return $next($request);
        }

        // 3. EXCEPCIÓN: Elº Superadmin tiene pase libre total
        if ($user->hasRole('Superadmin')) {
            return $next($request);
        }

        // --- INICIO DEL EMBUDO DE VALIDACIÓN ---

        // PASO A: Validar Aprobación
        // Si el estatus no es 'approved', lo mandamos a la espera (a menos que ya esté ahí)
        if ($user->status !== 'approved') {
            if (!$request->routeIs('espera.aprobacion')) {
                return redirect()->route('espera.aprobacion');
            }
            return $next($request);
        }

        // PASO B: Validar Registro de Negocio (Tenant)
        // Si está aprobado pero no tiene tenant_id, lo mandamos a configurar su negocio
        if (!$user->tenant_id) {
            // Permitimos que esté en la página de setup o que use Livewire dentro de esa página
            if (!$request->routeIs('filament.admin.pages.setup-business') && !$request->hasHeader('X-Livewire')) {
                return redirect()->route('filament.admin.pages.setup-business');
            }
        }

        // Si pasó todas las validaciones, puede continuar a la ruta solicitada
        return $next($request);
    }
}