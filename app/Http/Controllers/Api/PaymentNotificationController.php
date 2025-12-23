<?php

namespace App\Http\Controllers\Api;

use App\Events\PaymentReceived;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaymentParserManager; // Importamos el Manager Universal
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentNotificationController extends Controller
{
    /**
     * Recibe notificaciones de cualquier banco y las procesa automáticamente.
     */
    public function receiveSms(Request $request, PaymentParserManager $parserManager)
    {
        Log::info('Notificación de pago recibida:', $request->all());

        $request->validate([
            'message' => 'required|string',
            'tenant_id' => 'required|string',
            'payment_gateway_id' => 'required|integer',
        ]);

        // USAMOS EL MANAGER: Él probará BDV, BNC, Banesco, etc.
        $data = $parserManager->parse($request->message);

        // Si ningún banco pudo parsear el mensaje
        if (!$data) {
            return response()->json([
                'status' => 'ignored',
                'reason' => 'Formato de mensaje no reconocido por ningún banco registrado',
            ], 200);
        }

        // Evitar duplicados por referencia y tenant
        // Nota: Banesco usa referencias largas, asegúrate que tu columna sea String o BigInt
        $exists = Payment::where('reference', $data['referencia'])
            ->where('tenant_id', $request->tenant_id)
            ->exists();

        if ($exists) {
            return response()->json(['status' => 'duplicate'], 200);
        }

        // Crear el pago con la data estandarizada que devuelve cualquier Parser
        $payment = Payment::create([
            'tenant_id' => $request->tenant_id,
            'payment_gateway_id' => $request->payment_gateway_id,

            'amount' => $data['monto'],
            'payment_date' => substr($data['fecha'], 0, 10), // Y-m-d
            'remitter' => $data['remitente'],
            'phone_number' => $data['phone_number'] ?? null,
            'reference' => $data['referencia'],
            'bank' => $data['banco'], // Aquí se guardará "Banesco", "BNC" o "Banco de Venezuela"

            'notification_data' => [
                'raw_message' => $request->message,
                'parsed_info' => $data,
                'device_id' => $request->device_id,
            ],
            'notification_source' => 'sms',

            'status' => 'pending',
            'verified' => false,
            'verified_on' => null,
        ]);

        event(new PaymentReceived($payment));

        return response()->json([
            'status' => 'stored',
            'payment_id' => $payment->id,
            'detected_bank' => $data['banco'],
        ], 201);
    }
}