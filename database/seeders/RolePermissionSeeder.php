<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Psy\Util\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superadmin = User::factory()->create([
            'name' => 'Luis',
            'lastname' => 'Mujica',
            'email' => 'luismujica@test.com',
            'password' => bcrypt('1234'),
            'tenant_id' => null
        ]);

        $admin = User::factory()->create([
            'name' => 'Juan',
            'lastname' => 'Frias',
            'email' => 'juanfrias@test.com',
            'password' => bcrypt('1234'),
            'tenant_id' => null
        ]);


        $superadminrole = Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => 'web']);
        $adminrole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $employeerole = Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);

        $permissions = [
            // Superadmin permissions
            'manage_users',
            'manage_employees',
            'view_any_tenant',
            'create_tenant',
            'update_tenant',
            'delete_tenant',
            
            // Tenant Admin permissions
            'manage_payment_gateways',
            'manage_employees',
            'view_payments',
            'verify_payments',
            'export_payments',
            
            // Employee permissions (solo lectura)
            'view_payment_gateways',
            'view_payments_readonly',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        $superadminrole->givePermissionTo(Permission::all());
        
        $adminrole->givePermissionTo([
            'manage_payment_gateways',
            'manage_employees',
            'view_payments',
            'verify_payments',
            'export_payments',
        ]);

        $employeerole->givePermissionTo([
            'view_payment_gateways',
            'view_payments_readonly',
            ]);

        //Sincronizar rol
        $superadmin->assignRole('Superadmin');
        $admin->assignRole('Admin');

        // CREAR TENANT - MANUALMENTE CON UUID
        
        $tenant = Tenant::create([
            'owner_id' => $admin->id,
            'business_name' => 'Negocio de Juan Frias',
            'rif' => 'J-12345678-9',
            'slug' => 'empresa-juan',
            'data' => ['plan' => 'premium'],
        ]);

        $tenantId = $tenant->id;

        // Crear dominio
        $tenant->domains()->create([
            'domain' => 'empresa-juan.test',
            'tenant_id' => $tenantId, // <-- Asegurar mismo ID
        ]);

        // Actualizar usuario
        $admin->tenant_id = $tenantId;
        $admin->save();

        // Tabla pivote
        $admin->tenants()->attach($tenantId, ['role_in_tenant' => 'admin']);
    }
}
