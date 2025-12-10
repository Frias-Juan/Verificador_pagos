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
        
        $admin = User::factory()->create([
            'name' => 'Juan',
            'lastname' => 'Frias',
            'email' => 'juanfrias@test.com',
            'password' => bcrypt('1234'),
        ]);

    $tenant = Tenant::create([
            'owner_id' => $admin->id,
            'business_name' => 'Panaderia la floresta',
            'rif' => 'J-12345678-9',
            'slug' => 'panaderia-floresta',
        ]);

        $admin->tenant_id = $tenant->id;
        $admin->save();

        $roles = ['Superadmin', 'Admin', 'Employee'];
        foreach($roles as $roleName)
        {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $superadmin->assignRole('Superadmin');
        $admin->assignRole('Admin');

        $admin->tenants()->attach($admin->tenant_id, ['role_in_tenant' => 'admin']);

        $employee = User::create([
        'name' => 'Carlos',
        'lastname' => 'Marquez',
        'email' => 'carlosempleado@panaderia.com',
        'password' => bcrypt('1234'),
        'tenant_id' => $tenant->id,
    ]);
        $employee->assignRole('Employee');

        $tenantId = $tenant->id;

     $gatewayData = [
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
        
        $payments = [
            [
                'tenant_id' => $tenantId,
                'payment_gateway_id' => PaymentGateway::where('code', 'BDV_PM')->first()->id,
                'amount' => 150.75,
                'payment_date' => now()->subDays(3),
                'remitter' => 'Juan Pérez',
                'phone_number' => '+584123456789',
                'reference' => 123456,
                'bank' => 'Banco de Venezuela',
                'verified' => true,
                'verified_on' => now()->subDays(2),
                'status' => 'verified',
                'notification_source' => 'sms',
                'notification_data' => json_encode([
                    'raw_message' => 'BDV: PAGO MOVIL RECIBIDO. MONTO: 150.75. REF: 123456. DE: JUAN PEREZ',
                    'parsed_amount' => '150.75',
                    'parsed_reference' => '123456',
                    'received_at' => now()->subDays(3)->toDateTimeString(),
                ]),
            ],
                [
                'tenant_id' => $tenantId,
                'payment_gateway_id' => PaymentGateway::where('code', 'BDV_PM')->first()->id,
                'amount' => 200.00,
                'payment_date' => now()->subHours(2),
                'remitter' => 'Luis García',
                'phone_number' => '+584147258369',
                'reference' => 456789,
                'bank' => 'Banco de Venezuela',
                'verified' => false,
                'verified_on' => null,
                'status' => 'pending_verification',
                'notification_source' => 'sms',
                'notification_data' => json_encode([
                    'raw_message' => 'BDV: PAGO MOVIL POR 200.00 BS. REF: 456789. DE: LUIS GARCIA. FECHA: ' . now()->subHours(2)->format('d/m/Y H:i'),
                    'parsed_amount' => '200.00',
                    'parsed_reference' => '456789',
                    'received_at' => now()->subHours(2)->toDateTimeString(),
                ]),
            ]
            ];

            foreach ($payments as $paymentData) {
    $exists = Payment::where('tenant_id', $tenantId)
        ->where('reference', $paymentData['reference'])
        ->exists();
    
    if (!$exists) {
        Payment::create($paymentData);
    } 
    }
}
}
