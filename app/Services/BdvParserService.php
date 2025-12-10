<?php

namespace App\Services;

use DateTime;

class BdvParserService
{
    /**
     * Procesa un mensaje SMS de Banco de Venezuela y devuelve los datos del pago
     */
    public function procesarBdv(string $message): ?array
    {
        if (stripos($message, 'Recibiste') === false && stripos($message, 'PagomovilBDV') === false) {
            return null; // No es un mensaje válido
        }

        $banco = "Banco de Venezuela";
        $monto = null;
        $remitente = null;
        $referencia = null;
        $telefono = null;

        // Valores por defecto
        $fecha = date("d-m-y");
        $hora = date("H:i");

        // Detectar formato nuevo (con "de NOMBRE" y "Bs." y número de operación)
        $esFormatoNuevo = preg_match('/PagomovilBDV\s+de/i', $message);

        // Detectar formato viejo (con Ref)
        $esFormatoViejo = preg_match('/Ref[:\s]+/i', $message);

        /* FORMATO NUEVO */
        if ($esFormatoNuevo) {
            preg_match('/Bs\.?\s*([\d\.,]+)/i', $message, $montoMatch);
            preg_match('/de\s+([A-ZÁÉÍÓÚÑa-záéíóúñ ]+)\s+por/i', $message, $nombreMatch);
            preg_match('/(?:operacion|número de operacion|número de operación)\s+(\d+)/i', $message, $refMatch);
            preg_match('/fecha\s+(\d{2}-\d{2}-\d{2,4})/i', $message, $fechaMatch);
            preg_match('/hora\s*[:]?(\d{2}:\d{2})/i', $message, $horaMatch);

            $monto = $montoMatch[1] ?? null;
            $remitente = $nombreMatch[1] ?? null;
            $referencia = $refMatch[1] ?? null;

            if (isset($fechaMatch[1])) {
                $fecha = $fechaMatch[1];
            }
            if (isset($horaMatch[1])) {
                $hora = $horaMatch[1];
            }
        }
        /* FORMATO VIEJO */
        elseif ($esFormatoViejo) {
            preg_match('/Bs\.?\s*([\d\.,]+)/i', $message, $montoMatch);
            preg_match('/del\s+([0-9-]+)/i', $message, $telefonoMatch);
            preg_match('/Ref[:\s]+(\d+)/i', $message, $refMatch);
            preg_match('/fecha\s+(\d{2}-\d{2}-\d{2,4})/i', $message, $fechaMatch);
            preg_match('/hora\s*[:]?(\d{2}:\d{2})/i', $message, $horaMatch);

            $monto = $montoMatch[1] ?? null;
            $telefono = $telefonoMatch[1] ?? null;
            $referencia = $refMatch[1] ?? null;

            if (isset($fechaMatch[1])) {
                $fecha = $fechaMatch[1];
            }
            if (isset($horaMatch[1])) {
                $hora = $horaMatch[1];
            }
        }

        // Si no se pudo detectar la referencia, ignorar
        if (!$referencia) {
            return null;
        }

        // Convertir monto latino a float
        $monto = $this->convertirFormatoLatinoAFloat($monto);

        // Formatear fecha para SQL
        $fechaSQL = DateTime::createFromFormat('d-m-y H:i', "$fecha $hora");
        if (!$fechaSQL) {
            $fechaSQL = new DateTime(); // fallback
        }

        return [
            'remitente'  => $remitente ?? '',
            'monto'      => $monto ?? 0,
            'referencia' => $referencia,
            'phone_number' => $telefono,
            'fecha'      => $fechaSQL->format('Y-m-d H:i:s'),
            'banco'      => $banco,
        ];
    }

    /**
     * Convierte un monto en formato latino (1.234,56) a float
     */
    private function convertirFormatoLatinoAFloat(?string $valor): ?float
    {
        if (!$valor) return null;
        $valor = str_replace(['.', ','], ['', '.'], $valor);
        return (float) $valor;
    }
}
