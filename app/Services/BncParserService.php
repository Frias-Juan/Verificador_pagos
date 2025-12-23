<?php

namespace App\Services;

use App\Contracts\PaymentParserInterface;
use DateTime;

class BncParserService implements PaymentParserInterface
{
    public function parse(string $message): ?array
    {
        // 1. Validación rápida: ¿Contiene la marca BNC?
        if (stripos($message, 'BNC') === false) {
            return null;
        }

        $banco = "Banco Nacional de Crédito";

        /**
         * Formato BNC analizado:
         * "PAGO MOVIL RECIBIDO BNC Pago Movil Recibido Bs.0,10 Telf.0424***7743 Dia:22/12/25-11:40 Ref:609681523..."
         */

        // Extraer Monto (Ej: Bs.0,10)
        preg_match('/Bs\.?\s*([\d\.,]+)/i', $message, $montoMatch);
        
        // Extraer Teléfono (Ej: Telf.0424***7743)
        preg_match('/Telf\.?([\d\*]+)/i', $message, $telfMatch);
        
        // Extraer Fecha y Hora (Ej: Dia:22/12/25-11:40)
        preg_match('/Dia:(\d{2}\/\d{2}\/\d{2,4})-(\d{2}:\d{2})/i', $message, $dateTimeMatch);
        
        // Extraer Referencia (Ej: Ref:609681523)
        preg_match('/Ref:(\d+)/i', $message, $refMatch);

        $referencia = $refMatch[1] ?? null;

        // Si no hay referencia, no es un pago procesable
        if (!$referencia) {
            return null;
        }

        // Procesar Fecha: Convertir de DD/MM/YY a Y-m-d
        $fechaRaw = $dateTimeMatch[1] ?? date('d/m/y');
        $horaRaw = $dateTimeMatch[2] ?? date('H:i');
        
        // El BNC usa "/" y nosotros necesitamos "-" para DateTime en algunos casos
        $fechaSQL = DateTime::createFromFormat('d/m/y H:i', "$fechaRaw $horaRaw");

        return [
            'remitente'    => 'Cliente BNC', // El BNC no suele incluir el nombre en el texto
            'monto'        => $this->convertirFormatoLatinoAFloat($montoMatch[1] ?? '0'),
            'referencia'   => $referencia,
            'phone_number' => $telfMatch[1] ?? null,
            'fecha'        => $fechaSQL ? $fechaSQL->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
            'banco'        => $banco,
        ];
    }

    private function convertirFormatoLatinoAFloat(?string $valor): ?float
    {
        if (!$valor) return null;
        $valor = str_replace(['.', ','], ['', '.'], $valor);
        return (float) $valor;
    }
}