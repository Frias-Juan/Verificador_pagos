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

        if ($user->hasRole('Superadmin')) {
            return $next($request);
        }

        if ($user->status !== 'approved') {
            if (!$request->routeIs('espera.aprobacion')) {
                return redirect()->route('espera.aprobacion');
            }
            return $next($request);
        }

        if (!$user->tenant_id) {
            if (!$request->routeIs('filament.admin.pages.setup-business') && !$request->hasHeader('X-Livewire')) {
                return redirect()->route('filament.admin.pages.setup-business');
            }
        }

        return $next($request);
    }
}