// routes/api.php
Route::post('/api/payment-notifications', [PaymentNotificationController::class, 'store'])->middleware('auth:sanctum');

// app/Http/Controllers/Api/PaymentNotificationController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PaymentNotificationController extends Controller
{
    public function store(Request $request)
    {
        // Validar entrada
        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
            'phone' => 'required|string',
            'timestamp' => 'required|numeric',
            'device_id' => 'required|string', // Para seguridad
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar que el dispositivo esté autorizado
        if (!$this->isDeviceAuthorized($request->device_id, auth()->id())) {
            return response()->json([
                'success' => false,
                'message' => 'Dispositivo no autorizado'
            ], 403);
        }

        // Parsear el mensaje SMS
        $parsedData = $this->parseSMSMessage($request->message);
        
        // Buscar pago pendiente que coincida
        $payment = $this->findMatchingPayment($parsedData, auth()->user()->tenant_id);

        if ($payment) {
            // Actualizar pago con la notificación
            $payment->update([
                'notification_data' => [
                    'raw_message' => $request->message,
                    'parsed_data' => $parsedData,
                    'received_at' => now(),
                    'from_phone' => $request->phone,
                ],
                'notification_source' => 'sms',
                'phone_number' => $request->phone,
                'remitter' => $parsedData['remitter_name'] ?? $parsedData['from_account'] ?? 'Desconocido',
                'status' => 'pending_verification', // Cambiar a un estado intermedio
            ]);

            
            $this->notifyAdmin($payment);

            return response()->json([
                'success' => true,
                'message' => 'Notificación recibida y procesada',
                'payment_id' => $payment->id,
                'payment' => $payment
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No se encontró pago pendiente que coincida'
        ], 404);
    }

    private function parseSMSMessage(string $message): array
    {
        // Patrones comunes para bancos venezolanos
        $patterns = [
            'amount' => [
                '/MONTO[:]?\s*([0-9]+[.,]?[0-9]*)/i',
                '/MONTO Bs\.?\s*([0-9]+[.,]?[0-9]*)/i',
                '/DE\s*([0-9]+[.,]?[0-9]*)/i',
            ],
            'reference' => [
                '/REF[:]?\s*([0-9]+)/i',
                '/NRO[:]?\s*([0-9]+)/i',
                '/REFERENCIA[:]?\s*([0-9]+)/i',
            ],
            'remitter_name' => [
                '/DE\s*([A-Z\s]+)\s*CEDULA/i',
                '/DE\s*([A-Z\s]+)\s*TELF/i',
                '/DE\s*([A-Z\s]+)\s*BANCO/i',
            ],
            'from_account' => [
                '/CTA\s*([0-9]+)/i',
                '/CUENTA\s*([0-9]+)/i',
            ],
            'bank' => [
                '/(BANESCO|BDV|BANCO DE VENEZUELA|PROVINCIAL|BANCO|BANCAMIGA|BFC)/i',
            ],
        ];

        $result = [];

        foreach ($patterns as $key => $patternList) {
            foreach ($patternList as $pattern) {
                if (preg_match($pattern, $message, $matches)) {
                    $result[$key] = trim($matches[1]);
                    break;
                }
            }
        }

        // Intentar extraer fecha
        if (preg_match('/(\d{2}[\/\-]\d{2}[\/\-]\d{4})/', $message, $dateMatch)) {
            $result['date'] = $dateMatch[1];
        }

        // Determinar el banco basado en el contenido
        if (!isset($result['bank'])) {
            if (str_contains($message, 'BANESCO')) $result['bank'] = 'BANESCO';
            elseif (str_contains($message, 'BDV')) $result['bank'] = 'BDV';
            elseif (str_contains($message, 'PROVINCIAL')) $result['bank'] = 'PROVINCIAL';
            elseif (str_contains($message, 'BANCAMIGA')) $result['bank'] = 'BANCAMIGA';
        }

        return $result;
    }

    private function findMatchingPayment(array $parsedData, $tenantId): ?Payment
    {
        // Buscar por referencia
        if (isset($parsedData['reference'])) {
            $payment = Payment::where('tenant_id', $tenantId)
                ->where('reference', $parsedData['reference'])
                ->where('status', 'pending')
                ->first();

            if ($payment) return $payment;
        }

        // Buscar por monto y fecha aproximada (si no hay referencia)
        if (isset($parsedData['amount'])) {
            $amount = (float) str_replace(',', '.', $parsedData['amount']);
            
            $payment = Payment::where('tenant_id', $tenantId)
                ->whereBetween('amount', [$amount * 0.95, $amount * 1.05]) // ±5%
                ->whereDate('payment_date', today()) // Pagos de hoy
                ->where('status', 'pending')
                ->first();

            return $payment;
        }

        return null;
    }


    private function notifyAdmin(Payment $payment): void
    {
        // Puedes implementar:
        // 1. Notificación en Filament
        
        Log::info('Nueva notificación de pago recibida', [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'reference' => $payment->reference,
        ]);
    }
}