<?php
// app/Http/Middleware/RestrictMainPanelAccess.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictMainPanelAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        // ⚠️ Si el usuario es un Empleado, redirigirlo a su portal exclusivo
        if ($user && $user->hasRole('Employee')) {
            return redirect('/employee'); 
        }

        return $next($request);
    }
}