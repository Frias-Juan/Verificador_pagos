<?php
// app/Http/Middleware/RestrictPanelAccess.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictPanelAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        // Permitir acceso solo a 'Admin' y 'Employee'
        if ($user && $user->hasAnyRole(['Superadmin','Admin', 'Employee'])) {
            return $next($request);
        }
        
        // Si no tiene permisos, cerrar sesión y redirigir al login (por seguridad)
        auth()->logout();
        return redirect()->route('filament.employee.auth.login'); 
    }
}