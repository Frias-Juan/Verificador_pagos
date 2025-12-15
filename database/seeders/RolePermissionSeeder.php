<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
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
        
        $superadminRole = Role::where('name', 'Superadmin')->first();
        $adminRole = Role::where('name', 'Admin')->first();
        $employeeRole = Role::where('name', 'Employee')->first();
        $superadmin->assignRole($superadminRole);
        $resourcePermissions = [
            'users', 
            'payments', 
            'paymentgateways', 
            'tenants', 
            'roles', 
            'permissions' 
        ];

        $actions = ['view_any', 'view', 'create', 'update', 'delete', 'restore', 'force_delete'];

        $permissionsToCreate = [];
        
        // Generar todos los permisos CRUD
        foreach ($resourcePermissions as $resource) {
            foreach ($actions as $action) {
                $permissionsToCreate[] = "{$action}_{$resource}::resource";
            }
        }
        
        // Permisos específicos que no son CRUD de Filament, como el de "verificar"
        // Ojo: Si "verificar pagos" es una acción de Filament Table Action, 
        // Filament lo maneja con 'delete_payment::resource' si es una acción que cambia el estado. 
        // Usaremos una acción específica:
        $permissionsToCreate[] = 'verify_payment'; // Permitir verificar pagos
        
        // Crear todos los permisos definidos
        foreach ($permissionsToCreate as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        Permission::firstOrCreate(['name' => 'view_any_all_resources']); 
        $superadminRole->syncPermissions(Permission::all()->pluck('name'));
        

        $adminPermissions = [
            // Ver Resources (Menú de navegación)
            'view_any_payment::resource', 
            'view_any_paymentgateway::resource',
            'view_tenant::resource',
            
            // Acciones Permitidas en Pagos
            'view_payment::resource',
            'delete_payment::resource', // Permitido eliminar
            'verify_payment', // Permitido verificar (acción específica)
            
            // Acciones Permitidas en sus propios Resources
            'view_paymentgateway::resource',
            'view_tenant::resource',
            'update_paymentgateway::resource', 
        ];
        $adminRole->syncPermissions($adminPermissions);

        $employeePermissions = [
            'view_payment::resource',
            'verify_payment', 
        ];
        $employeeRole->syncPermissions($employeePermissions);
    

    }
}
