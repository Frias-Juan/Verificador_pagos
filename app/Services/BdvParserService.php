<?php

namespace App\Services;

use App\Contracts\PaymentParserInterface;
use DateTime;

class BdvParserService implements PaymentParserInterface
{
    /**
     * Procesa un mensaje de BDV cumpliendo con el contrato universal.
     */
    public function parse(string $message): ?array
    {
        // 1. Validación rápida: ¿Es este mensaje del BDV?
        if (stripos($message, 'Recibiste') === false && stripos($message, 'PagomovilBDV') === false) {
            return null;
        }

        $banco = "Banco de Venezuela";
        $monto = null;
        $remitente = null;
        $referencia = null;
        $telefono = null;

        // Valores por defecto
        $fecha = date("d-m-y");
        $hora = date("H:i");

        // Detectar formatos
        $esFormatoNuevo = preg_match('/PagomovilBDV\s+de/i', $message);
        $esFormatoViejo = preg_match('/Ref[:\s]+/i', $message);

        /* LÓGICA DE EXTRACCIÓN (Tu código original de Regex) */
        if ($esFormatoNuevo) {
            preg_match('/Bs\.?\s*([\d\.,]+)/i', $message, $montoMatch);
            preg_match('/de\s+([A-ZÁÉÍÓÚÑa-záéíóúñ ]+)\s+por/i', $message, $nombreMatch);
            preg_match('/(?:operacion|número de operacion|número de operación)\s+(\d+)/i', $message, $refMatch);
            preg_match('/fecha\s+(\d{2}-\d{2}-\d{2,4})/i', $message, $fechaMatch);
            preg_match('/hora\s*[:]?(\d{2}:\d{2})/i', $message, $horaMatch);

            $monto = $montoMatch[1] ?? null;
            $remitente = $nombreMatch[1] ?? null;
            $referencia = $refMatch[1] ?? null;
            $fecha = $fechaMatch[1] ?? $fecha;
            $hora = $horaMatch[1] ?? $hora;
        } 
        elseif ($esFormatoViejo) {
            preg_match('/Bs\.?\s*([\d\.,]+)/i', $message, $montoMatch);
            preg_match('/del\s+([0-9-]+)/i', $message, $telefonoMatch);
            preg_match('/Ref[:\s]+(\d+)/i', $message, $refMatch);
            preg_match('/fecha\s+(\d{2}-\d{2}-\d{2,4})/i', $message, $fechaMatch);
            preg_match('/hora\s*[:]?(\d{2}:\d{2})/i', $message, $horaMatch);

            $monto = $montoMatch[1] ?? null;
            $telefono = $telefonoMatch[1] ?? null;
            $referencia = $refMatch[1] ?? null;
            $fecha = $fechaMatch[1] ?? $fecha;
            $hora = $horaMatch[1] ?? $hora;
        }

        // Si no hay referencia, no podemos validar el pago
        if (!$referencia) {
            return null;
        }

        // Normalización de datos
        $montoFloat = $this->convertirFormatoLatinoAFloat($monto);
        
        $fechaSQL = DateTime::createFromFormat('d-m-y H:i', "$fecha $hora");
        if (!$fechaSQL) {
            $fechaSQL = new DateTime();
        }

        // RETORNO ESTANDARIZADO (Lo que el Manager espera recibir)
        return [
            'remitente'    => $remitente ?? '',
            'monto'        => $montoFloat ?? 0,
            'referencia'   => $referencia,
            'phone_number' => $telefono,
            'fecha'        => $fechaSQL->format('Y-m-d H:i:s'),
            'banco'        => $banco,
        ];
    }

    /**
     * Helper privado para manejar los montos venezolanos.
     */
    private function convertirFormatoLatinoAFloat(?string $valor): ?float
    {
        if (!$valor) return null;
        $valor = str_replace(['.', ','], ['', '.'], $valor);
        return (float) $valor;
    }
}