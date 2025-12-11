<?php

namespace App\Filament\Resources\UsersResource\Pages;

use App\Filament\Resources\UsersResource;
use App\Models\PaymentGateway;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Tenant;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Model;

class CreateUsers extends CreateRecord
{
    protected static string $resource = UsersResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $roleId = $data['roles'];
        $roleName = Role::find($roleId)?->name;
        $creator = auth()->user();

        // -------------------------
        // LOGICA PARA ADMIN (CREAR TENANT)
        // -------------------------
        if ($roleName === 'Admin') {
            // 1. Crear el Tenant
            $tenant = Tenant::create([
                'business_name' => $data['business_name'],
                'address'       => $data['address'] ?? null,
                'owner_id'      => null, // Lo actualizaremos en afterCreate
            ]);

            // 2. Asignar el ID del nuevo Tenant al usuario a crear
            $data['tenant_id'] = $tenant->id;
            
        } 
        
        // -------------------------
        // LOGICA PARA EMPLOYEE (Asignación automática si el creador es Admin)
        // -------------------------
        elseif ($roleName === 'Employee') {
            
            // Si el creador es Admin, asignación automática a su tenant
            if ($creator && $creator->hasRole('Admin') && $creator->tenant_id) {
                $data['tenant_id'] = $creator->tenant_id;
            } 
            // Si el creador es Superadmin, el tenant_id viene del formulario y se mantiene.
        }

        // Limpiamos los campos que no pertenecen a la tabla users
        unset($data['business_name'], $data['address']);

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var \App\Models\User $user */
        $user = $this->record;

        $roleName = $user->roles()->first()?->name;
        $tenantId = $user->tenant_id;
        
        // Si no tiene tenant ID, no hacemos nada más (ej: Superadmin, o Employee sin asignar)
        if (!$tenantId) {
            return;
        }

        // ----------------------------------------
        // ADMIN: Asignar Owner ID y llenar tabla pivote
        // ----------------------------------------
        if ($roleName === 'Admin') {
            
            // 1. Asignar Owner ID (si no está asignado)
            $tenant = Tenant::find($tenantId);
            if ($tenant && is_null($tenant->owner_id)) {
                $tenant->update(['owner_id' => $user->id]);
            }
            
            // 2. Adjuntar a la tabla pivote como 'owner'
            $user->tenants()->attach($tenantId, [
                'role_in_tenant' => 'owner'
            ]);
        }

        // 3. REGISTRAR LA PASARELA DE PAGO MÓVIL
            
            if (!empty($this->data['name'])) {
                
                PaymentGateway::create([
                    'tenant_id' => $tenantId,
                    'name' => $this->data['name'], // Nombre del Banco (Campo 'name')
                    'type' => 'PAGOMOVIL',                  // Código de la pasarela
                   // Número de Teléfono (Campo 'api_key')
                    'is_active' => true,
                ]);
            }
            
            // Opcional: Limpiamos los campos temporales del array $data después de usarlos
            unset(
                $this->data['pm_bank_name'], 
                $this->data['pm_phone']
            );

        // ----------------------------------------
        // EMPLOYEE: Llenar tabla pivote
        // ----------------------------------------
        if ($roleName === 'Employee') {
            
            // Adjuntar a la tabla pivote como 'employee'
            $user->tenants()->attach($tenantId, [
                'role_in_tenant' => 'employee'
            ]);
        }
    }
    
    // Opcional: Redirigir al índice después de crear
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}