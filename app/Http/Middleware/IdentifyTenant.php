<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
         // Si el usuario está autenticado
        if (Auth::check()) {
            $user = Auth::user();
            
            // Si es Superadmin, no aplicar tenant
            if ($user->isSuperAdmin()) {
                return $next($request);
            }
            
            // Para usuarios de tenant: identificar tenant actual
            if (!$user->tenant_id && $user->tenants()->exists()) {
                // Si no tiene tenant_id asignado pero tiene tenants relacionados
                // Asignar el primer tenant (podrías mejorar esta lógica)
                $tenant = $user->tenants()->first();
                $user->tenant_id = $tenant->id;
                $user->save();
            }
            
            // Inicializar tenancy si el usuario tiene tenant
            if ($user->tenant_id) {
                $tenant = Tenant::find($user->tenant_id);
                if ($tenant) {
                    tenancy()->initialize($tenant);
                }
            }
        }
        
        return $next($request);
    }
        
}
