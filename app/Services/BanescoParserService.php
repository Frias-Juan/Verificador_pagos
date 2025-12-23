<?php

namespace App\Services;

use App\Contracts\PaymentParserInterface;
use DateTime;

class BanescoParserService implements PaymentParserInterface
{
    public function parse(string $message): ?array
    {
        // 1. Validación rápida: ¿Contiene la marca BANESCO?
        if (stripos($message, 'BANESCO') === false) {
            return null;
        }

        $banco = "Banesco";

        /**
         * Formato Banesco analizado:
         * "BANESCO REGISTRO: Pago recibido a traves de Pago Movil por Bs. 1.0 el 22/12/2025; 11:53 REF 004609865975..."
         */

        // Extraer Monto (Ej: Bs. 1.0)
        preg_match('/Bs\.?\s*([\d\.,]+)/i', $message, $montoMatch);
        
        // Extraer Fecha y Hora (Ej: 22/12/2025; 11:53)
        // Buscamos el patrón DD/MM/YYYY seguido de ";" y HH:MM
        preg_match('/(\d{2}\/\d{2}\/\d{4});\s*(\d{2}:\d{2})/i', $message, $dateTimeMatch);
        
        // Extraer Referencia (Ej: REF 004609865975)
        preg_match('/REF\s*(\d+)/i', $message, $refMatch);

        $referencia = $refMatch[1] ?? null;

        // Si no hay referencia, no es un pago procesable
        if (!$referencia) {
            return null;
        }

        // Procesar Fecha: Convertir de DD/MM/YYYY a Y-m-d
        $fechaRaw = $dateTimeMatch[1] ?? date('d/m/Y');
        $horaRaw = $dateTimeMatch[2] ?? date('H:i');
        
        // Banesco usa el año completo (2025), usamos 'Y' mayúscula en el formato
        $fechaSQL = DateTime::createFromFormat('d/m/Y H:i', "$fechaRaw $horaRaw");

        return [
            'remitente'    => 'Cliente Banesco', // El mensaje no incluye nombre del emisor
            'monto'        => $this->convertirFormatoLatinoAFloat($montoMatch[1] ?? '0'),
            'referencia'   => $referencia,
            'phone_number' => null, // Banesco no envía el teléfono del emisor en este formato
            'fecha'        => $fechaSQL ? $fechaSQL->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
            'banco'        => $banco,
        ];
    }

    private function convertirFormatoLatinoAFloat(?string $valor): ?float
    {
        if (!$valor) return null;
        // Normalizamos: quitamos puntos de miles y cambiamos coma decimal por punto
        $valor = str_replace(['.', ','], ['', '.'], $valor);
        return (float) $valor;
    }
}