<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Contracts\Permission as ContractsPermission;
use Spatie\Permission\Models\Permission as ModelsPermission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $superadmin = User::factory()->create([
            'name' => 'Luis',
            'lastname' => 'Mujica',
            'email' => 'luismujica@test.com',
            'password' => bcrypt('1234')

        
        ]);

        $admin = User::factory()->create([
            'name' => 'Juan',
            'lastname' => 'Frias',
            'email' => 'juanfrias@test.com',
            'password' => bcrypt('1234')

        
        ]);


        $superadminrole = Role::firstOrCreate(['name' => 'Superadmin']);
        $adminrole = Role::firstOrCreate(['name' => 'Admin']);
        $employeerole = Role::firstOrCreate(['name' => 'Employee']);
        $superadmin->assignRole($superadminrole);
        $admin->assignRole($adminrole);

        $permissions = [
            // Usuarios & Roles
            'manage-users',
            'view-users',
            'manage-roles',
            'manage-employees',
            'view-employees',
            // Tenants / Negocios
            'manage-tenants',
            'view-tenants',
            // Pasarelas y pagos
            'manage-payment-gateways',
            'view-payment-gateways',
            'manage-payments',
            'view-payments',
            // Config y sistema
            'manage-settings',
            'impersonate',
            // Registro / onboarding
            'register-admins',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }
        $superadmin->syncPermissions(Permission::all());
        $admin = [
            'manage-employees',
            'view-employees',
            'manage-payment-gateways',
            'view-payment-gateways',
            'manage-payments',
            'view-payments',
        ];

       
    }
}
