<?php

namespace App\Filament\Resources\UsersResource\Pages;

use App\Filament\Resources\UsersResource;
use App\Models\PaymentGateway;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Tenant;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreateUsers extends CreateRecord
{
    protected static string $resource = UsersResource::class;

    // 1. PROPIEDAD PÚBLICA para almacenar el ID del Tenant de forma temporal
    public ?string $createdTenantId = null; 

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = 'approved';
        
        $roleId = $data['roles'];
        $roleName = Role::find($roleId)?->name;
        $creator = auth()->user();

        // -------------------------
        // LOGICA PARA ADMIN (CREAR TENANT)
        // -------------------------
        if ($roleName === 'Admin') {
            
            $tenant = Tenant::create([
                'business_name' => $data['business_name'],
                'address'       => $data['address'] ?? null,
                'slug'          => Str::slug($data['business_name']),
                'owner_id'      => null,
            ]);

            $this->createdTenantId = (string) $tenant->id; 
            $data['tenant_id'] = $this->createdTenantId;
        } 
        
        // -------------------------
        // LOGICA PARA EMPLOYEE (Asignación automática si el creador es Admin)
        // -------------------------
        elseif ($roleName === 'Employee') {
            
            if ($creator && $creator->hasRole('Admin') && $creator->tenant_id) {
                $data['tenant_id'] = $creator->tenant_id;
            } 
        }

        unset($data['business_name'], $data['address']);
        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var \App\Models\User $user */
        $user = $this->record;
        $data = $this->data; 
        $roleName = $user->roles()->first()?->name;
        
        $tenantId = $roleName === 'Admin' ? $this->createdTenantId : $user->tenant_id;
        
        if (!$tenantId) {
            return;
        }

        // ----------------------------------------
        // 1. ADMIN: Asignar Owner ID y llenar tabla pivote (tenant_user)
        // ----------------------------------------
        if ($roleName === 'Admin') {
            $tenant = Tenant::find($tenantId);
            
            if ($tenant && is_null($tenant->owner_id)) {
                $tenant->update(['owner_id' => $user->id]);
            }
            
            // Llena la tabla pivote tenant_user
            $user->tenants()->attach($tenantId, [
                'role_in_tenant' => 'owner'
            ]);
        }

        // ----------------------------------------
        // 2. REGISTRAR Y ASOCIAR PASARELA (Many-to-Many)
        // ----------------------------------------
        $initialGateways = $data['initial_gateways'] ?? [];
        $createdGatewaysIds = [];

        foreach ($initialGateways as $gatewayData) {
            
            $type = $gatewayData['gateway_type'] ?? null;
            $name = ($type === 'PAGOMOVIL') 
                ? ($gatewayData['gateway_name'] ?? null) 
                : (($type === 'ZELLE') ? ($gatewayData['zelle_name'] ?? null) : null);

            if ($name && $type) {
                // ⚠️ Se quita el tenant_id del criterio de búsqueda
                $gateway = PaymentGateway::firstOrCreate(
                    [
                        'type' => $type,
                        'name' => $name,
                    ],
                    [
                        'is_active' => true,
                    ]
                );
                $createdGatewaysIds[] = $gateway->id;
            }
        }
        
        // ⚠️ NUEVO PASO: Asociar las Pasarelas recién creadas/encontradas al Tenant.
        // Esto usa la tabla pivote payment_gateway_tenant.
        if ($roleName === 'Admin' && !empty($createdGatewaysIds)) {
            $tenant = Tenant::find($tenantId);
            if ($tenant) {
                // Sincroniza las pasarelas con el Tenant.
                $tenant->paymentGateways()->syncWithoutDetaching($createdGatewaysIds); 
            }
        }
        
        // ----------------------------------------
        // 3. EMPLOYEE: Llenar tabla pivote (tenant_user)
        // ----------------------------------------
        if ($roleName === 'Employee' && $tenantId) {
            
            // Llena la tabla pivote tenant_user
            $user->tenants()->attach($tenantId, [
                'role_in_tenant' => 'employee'
            ]);
        }
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}