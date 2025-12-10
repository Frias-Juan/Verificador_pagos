<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GenerateAPIToken extends Command
{
    protected $signature = 'api:token {email} {--device=MobileApp}';
    protected $description = 'Generar token API para un usuario';

    public function handle()
    {
        $user = User::where('email', $this->argument('email'))->first();
        
        if (!$user) {
            $this->error('Usuario no encontrado');
            return 1;
        }

        if ($user->hasRole('Superadmin')) {
            $this->error('Superadmin no puede generar tokens API');
            return 1;
        }

        $token = $user->createToken($this->option('device'));

        $this->info('✅ Token generado exitosamente!');
        $this->line('');
        $this->line('📱 Token: ' . $token->plainTextToken);
        $this->line('👤 Usuario: ' . $user->email);
        $this->line('🏢 Tenant: ' . $user->tenant_id);
        $this->line('📱 Dispositivo: ' . $this->option('device'));
        $this->line('');
        $this->info('🔧 Para usar:');
        
        $curlCommand = 'curl -X POST ' . url('/api/sms-notifications') . ' \\' . PHP_EOL;
        $curlCommand .= '  -H "Authorization: Bearer ' . $token->plainTextToken . '" \\' . PHP_EOL;
        $curlCommand .= '  -H "Content-Type: application/json" \\' . PHP_EOL;
        $curlCommand .= '  -d \'{"message": "SMS de prueba", "phone": "04120000000", "timestamp": 1234567890, "device_id": "android123"}\'';
        
        $this->line($curlCommand);

        return 0;
    }
}