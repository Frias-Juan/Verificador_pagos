<?php

namespace App\Http\Controllers\Api;

use App\Events\PaymentReceived;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\BdvParserService;
use Illuminate\Http\Request;

class BdvPaymentController extends Controller
{
    public function receiveSms(Request $request, BdvParserService $parser)
    {

        \Log::info('Solicitud recibida:', $request->all());
        $request->validate([
            'message' => 'required|string',
            'tenant_id' => 'required|string',
            'payment_gateway_id' => 'required|integer',
        ]);

        // Parseamos el SMS
        $data = $parser->procesarBdv($request->message);

        if (!$data) {
            return response()->json([
                'status' => 'ignored',
                'reason' => 'Mensaje no coincide con BDV',
            ], 200);
        }

        // Evitar duplicados por referencia y tenant
        $exists = Payment::where('reference', $data['referencia'])
            ->where('tenant_id', $request->tenant_id)
            ->exists();

        if ($exists) {
            return response()->json(['status' => 'duplicate'], 200);
        }

        // Crear el pago con todos los campos de la migración
        $payment = Payment::create([
            'tenant_id' => $request->tenant_id,
            'payment_gateway_id' => $request->payment_gateway_id,

            'amount' => $data['monto'],
            'payment_date' => substr($data['fecha'], 0, 10), // solo fecha Y-m-d
            'remitter' => $data['remitente'],
            'phone_number' => $data['phone_number'] ?? null,
            'reference' => $data['referencia'],
            'bank' => $data['banco'],

            'notification_data' => [
                'raw_message' => $request->message,
                'parsed' => $data,
                'device_id' => $request->device_id,
            ],
            'notification_source' => 'sms',

            'status' => 'pending',           // coincidiendo con migración
            'verified' => false,
            'verified_on' => null,
        ]);

        event(new PaymentReceived($payment));

        return response()->json([
            'status' => 'stored',
            'payment_id' => $payment->id,
        ], 201);
    }
}
