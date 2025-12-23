<?php

namespace App\Contracts;

/**
 * Contrato universal para los parsers de bancos.
 * Garantiza que todos los bancos devuelvan la misma estructura de datos.
 */
interface PaymentParserInterface
{
    /**
     * Analiza el texto del mensaje y extrae la información del pago.
     * * @param string $message El contenido del SMS o notificación push.
     * @return array|null Retorna un array con datos normalizados o null si el formato no coincide.
     */
    public function parse(string $message): ?array;
}