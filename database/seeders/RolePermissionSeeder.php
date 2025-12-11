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
        
        $roles = ['Superadmin', 'Admin', 'Employee'];
        foreach($roles as $roleName)
        {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $superadmin->assignRole('Superadmin');

     /*$gatewayData = [
            'tenant_id' => $tenantId,
            'name' => 'Pago Móvil - Banco de Venezuela',
            'api_key' => 'bdv_pm_api_' . bin2hex(random_bytes(8)),
            'code' => 'BDV_PM',
            'fee_percentage' => 0,
            'is_active' => true,
        ];

        // Crear el gateway directamente
        PaymentGateway::firstOrCreate(
            [
                'code' => $gatewayData['code'], 
                'tenant_id' => $tenantId
            ],
            $gatewayData
        );

        $bdvGateway = PaymentGateway::where('code', 'BDV_PM')
            ->where('tenant_id', $tenantId)
            ->first();
        
        if (!$bdvGateway) {
            $this->command->error('❌ No se encontró el gateway BDV_PM');
            return;
        }
        */
    }
}
