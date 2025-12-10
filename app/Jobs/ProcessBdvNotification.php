<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use Stancl\Tenancy\Jobs\TenantAware; 
use DateTime; // Necesario para manejar fechas
use Stancl\Tenancy\Concerns\TenantAwareCommand;

class ProcessBdvNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TenantAware;

    protected $messageText;
    protected $tenantId;

    /**
     * Create a new job instance.
     *
     * @param string $messageText El texto completo del SMS
     * @param string $tenantId    El ID del inquilino (dueño de la panaderia)
     * @return void
     */
    public function __construct(string $messageText, string $tenantId)
    {
        $this->messageText = $messageText;
        $this->tenantId = $tenantId;
        $this->tenant = tenant($tenantId); // Asigna el tenant al trait TenantAware
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        // El trait TenantAware se encarga de cambiar a la BD del tenant automáticamente
        
        $parsedData = $this->procesarBdv($this->messageText);

        if (!$parsedData) {
            Log::warning("Mensaje BDV no pudo ser parseado: " . $this->messageText);
            return;
        }

        // Crear el registro de pago en la BD del inquilino actual
        Payment::create([
            'tenant_id'          => $this->tenantId,
            // Asume un payment_gateway_id por defecto para BDV.
            // Si necesitas buscarlo dinamicamente, avisa.
            'payment_gateway_id' => 1, 
            'amount'             => $parsedData['monto'],
            'payment_date'       => $parsedData['fecha'],
            'remitter'           => $parsedData['remitente'],
            'reference'          => $parsedData['referencia'],
            'bank'               => $parsedData['banco'],
            'notification_data'  => json_encode(['sms_text' => $this->messageText]),
            'notification_source'=> 'sms',
            'status'             => 'pending',
        ]);
        
        // Aquí iría la lógica para notificar al admin de Filament.
        // Usar Filament Notifications.
    }

    // --- Parser BDV y Funciones Auxiliares ---

    private function procesarBdv($message)
    {
         if (strpos($message, 'Recibiste') === false && strpos($message, 'PagomovilBDV') === false) {
        return false; // No es un mensaje de BDV válido
    }

    $banco = "Banco de Venezuela";
    $monto = null;
    $remitente = null;
    $referencia = null;

    // Valores por defecto (cuando BDV no envía fecha/hora)
    $fecha = date("d-m-y");
    $hora = date("H:i");

    // Detectar formato nuevo BDV (tiene "de NOMBRE" y "Bs.")
    $esFormatoNuevo = preg_match('/PagomovilBDV de/i', $message);

    // Detectar formato viejo BDV
    $esFormatoViejo = preg_match('/del\s+04/i', $message);


    /* ============================================================
       FORMATO NUEVO:
       "Recibiste un PagomovilBDV de NOMBRE COMPLETO por Bs.6.448,25  
        bajo el numero de operacion 004465846227"
       ============================================================ */
    if ($esFormatoNuevo) {

        // Monto
        preg_match('/Bs\.?\s*([\d\.,]+)/i', $message, $montoMatch);

        // Nombre completo (acepta mayúsculas, minúsculas y acentos)
        preg_match('/de\s+([A-ZÁÉÍÓÚÑa-záéíóúñ ]+)\s+por/i', $message, $nombreMatch);

        // Referencia
        preg_match('/operacion\s+(\d+)/i', $message, $refMatch);

        $monto = $montoMatch[1] ?? null;
        $remitente = $nombreMatch[1] ?? null;
        $referencia = $refMatch[1] ?? null;

    }
    /* ============================================================
       FORMATO VIEJO:
       "Bs. 5,00 del 0412-2701205 Ref 48310657064, 16/06/2025, 17:41:07"
       ============================================================ */
    elseif ($esFormatoViejo) {

        preg_match('/Bs\.?\s*([\d\.,]+)/i', $message, $montoMatch);
        preg_match('/del\s+([0-9-]+)/i', $message, $telefonoMatch);
        preg_match('/Ref[:\s]+(\d+)/i', $message, $refMatch);
        preg_match('/(\d{2}\/\d{2}\/\d{4})/i', $message, $fechaMatch);
        preg_match('/(\d{2}:\d{2}:\d{2})/i', $message, $horaMatch);

        $monto = $montoMatch[1] ?? null;
        $remitente = $telefonoMatch[1] ?? null;
        $referencia = $refMatch[1] ?? null;

        if (isset($fechaMatch[1])) {
            $fecha = DateTime::createFromFormat("d/m/Y", $fechaMatch[1])->format("d-m-y");
        }
        if (isset($horaMatch[1])) {
            $hora = substr($horaMatch[1], 0, 5);
        }

    }


    // Convertir monto latino a float seguro
    $monto = convertirFormatoLatinoAFloat($monto);

    // Fecha final SQL
    $fechaSQL = DateTime::createFromFormat("d-m-y", $fecha);

    return [
        'remitente'  => $remitente,
        'monto'      => $monto,
        'referencia' => $referencia,
        'fecha'      => $fechaSQL ? $fechaSQL->format('Y-m-d') . ' ' . $hora : null,
        'banco'      => $banco
    ];
    }

    /**
     * Convierte un monto con formato latino (coma decimal) a float (punto decimal).
     */
    private function convertirFormatoLatinoAFloat($monto) {
        if ($monto === null) return null;
        // Reemplaza puntos por nada (separador de miles), y la coma por punto (separador decimal)
        $monto = str_replace('.', '', $monto);
        $monto = str_replace(',', '.', $monto);
        return (float) $monto;
    }
}
