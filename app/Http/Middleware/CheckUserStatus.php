<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
       if (Auth::check()) {
        $user = Auth::user();

        // Si está pendiente de aprobación (el Superadmin no ha hecho clic en el botón)
        if ($user->hasRole('Admin') && $user->status === 'pending') {
            Auth::logout();
            return redirect()->route('filament.admin.auth.login')->with('notification', 'Esperando aprobación.');
        }

        // Si ya fue aprobado por el Superadmin pero NO ha configurado su negocio
        if ($user->hasRole('Admin') && $user->status === 'waiting_business' && !$user->tenant_id) {
            // Evitar bucle infinito de redirección
            if (!$request->routeIs('filament.admin.pages.setup-business')) {
                return redirect()->route('filament.admin.pages.setup-business');
            }
        }
        return $next($request);
    }
}
}