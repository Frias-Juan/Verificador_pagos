<?php

namespace App\Services;

use App\Contracts\PaymentParserInterface;
use Illuminate\Support\Facades\Log;

class PaymentParserManager
{
    /**
     * Lista de clases de los parsers de bancos registrados.
     * Aquí es donde "conectas" cada banco nuevo que creas.
     */
    protected array $parsers = [
        BdvParserService::class,
        BncParserService::class,
        BanescoParserService::class,
    ];

    /**
     * Intenta procesar el mensaje con cada uno de los parsers disponibles.
     */
    public function parse(string $message): ?array
    {
        foreach ($this->parsers as $parserClass) {
            try {
                // Instanciamos el parser usando el contenedor de Laravel
                /** @var PaymentParserInterface $parser */
                $parser = app($parserClass);

                // Le pedimos al banco que intente leer el mensaje
                $data = $parser->parse($message);

                // Si el banco devuelve datos, el proceso termina aquí exitosamente
                if ($data) {
                    return $data;
                }
            } catch (\Exception $e) {
                // Si un banco falla, lo logueamos pero seguimos probando con los demás
                Log::error("Error procesando pago con {$parserClass}: " . $e->getMessage());
            }
        }

        // Si recorrió todos y ninguno funcionó
        return null;
    }
}