<?php

namespace App\Modules\Payments\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Error devuelto por la API de Culqi.
 *
 * Arrastra el payload original porque los mensajes de Culqi son la única
 * fuente para diagnosticar: "no devolvió un token válido" no dice si fue un
 * BIN desconocido, un CVV malo o una caída.
 */
class CulqiApiException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        string $message,
        public readonly int $status,
        public readonly array $payload = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
    }

    /**
     * Mensaje que Culqi preparó para el comprador final.
     */
    public function userMessage(): ?string
    {
        $message = $this->payload['user_message'] ?? null;

        return is_string($message) && $message !== '' ? $message : null;
    }

    /**
     * Detalle técnico, solo para logs. Nunca se muestra al comprador.
     */
    public function merchantMessage(): ?string
    {
        $message = $this->payload['merchant_message'] ?? null;

        return is_string($message) && $message !== '' ? $message : null;
    }

    public function errorType(): ?string
    {
        $type = $this->payload['type'] ?? null;

        return is_string($type) && $type !== '' ? $type : null;
    }

    public function parameter(): ?string
    {
        $param = $this->payload['param'] ?? null;

        return is_string($param) && $param !== '' ? $param : null;
    }
}
