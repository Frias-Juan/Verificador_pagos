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
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
       $superadminRole = Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => 'web']);
        $adminRole      = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $employeeRole   = Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);

        $superadmin = User::firstOrCreate(
            ['email' => 'luismujica@test.com'],
            [
                'name' => 'Luis',
                'lastname' => 'Mujica',
                'password' => bcrypt('1234'),
                'tenant_id' => null,
                'status' => null
            ]
        );
        $superadmin->assignRole($superadminRole);

        $resourcePermissions = [
            'users',            
            'payments',         
            'paymentgateways',  
            'admins',          
            'roles', 
            'permissions' 
        ];

        $actions = ['view_any', 'view', 'create', 'update', 'delete', 'restore', 'force_delete'];
        $permissionsToCreate = [];
        
        foreach ($resourcePermissions as $resource) {
            foreach ($actions as $action) {
                $permissionsToCreate[] = "{$action}_{$resource}::resource";
            }
        }

        $permissionsToCreate[] = 'verify_payments';

        foreach ($permissionsToCreate as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        $superadminRole->syncPermissions(Permission::all());

        $adminRole->syncPermissions([
            'view_any_payments::resource', 
            'view_any_paymentgateways::resource',
            'view_any_users::resource', 
            'view_any_admins::resource', 
            
            // Acciones en Pagos
            'view_payments::resource',
            'delete_payments::resource',
            'verify_payments', 
            
            // Acciones en otros Resources
            'view_users::resource',
            'create_users::resource',
            'update_users::resource',
            'view_paymentgateways::resource',
            'update_paymentgateways::resource',
            'view_admins::resource',
        ]);

        // EMPLOYEE: Solo verificar (Panel personalizado)
        $employeeRole->syncPermissions([
            'verify_payments',
        ]);
    
    }
}
