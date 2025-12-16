<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeesResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class CreateEmployees extends CreateRecord
{
    protected static string $resource = EmployeesResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $admin = auth()->user();

       if ($admin->tenant_id) {
            $data['tenant_id'] = $admin->tenant_id;
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        // 1. Creamos el usuario en la tabla 'users'
        $user = static::getModel()::create($data);

        // 2. Vínculo con el Negocio (Tu tabla pivote tenant_user)
        $tenantId = auth()->user()->tenant_id;
        if ($tenantId) {
            $user->tenants()->attach($tenantId, [
                'role_in_tenant' => 'employee'
            ]);
        }

        // 3. Vínculo con el Rol de Spatie (Tabla pivote model_has_roles)
        // Usamos assignRole para que Spatie maneje su propia tabla
        $user->assignRole('Employee');

        return $user;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}