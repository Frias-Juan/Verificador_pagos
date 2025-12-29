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
        $data['status'] = 'approved';
        
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

        return $user;
    }

    protected function afterCreate(): void
    {
       /** @var \App\Models\User $user */
    $user = $this->record;
    $user->assignRole('Employee');
    
    $tenantId = auth()->user()->tenant_id;
    if ($tenantId) {
        // FORMA 1: Usar updateExistingPivot
        $user->tenants()->updateExistingPivot($tenantId, [
            'role_in_tenant' => 'employee'
        ]);
        
        // FORMA 2: Detach y attach explícito
        $user->tenants()->detach($tenantId);
        $user->tenants()->attach($tenantId, [
            'role_in_tenant' => 'employee',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}