<?php

namespace App\Modules\Payments\Gateways\Izipay;

use RuntimeException;
use Throwable;

/**
 * Error devuelto por la API REST de Izipay.
 *
 * Conserva el cuerpo original porque los códigos de error de Izipay
 * (ej. "INT_001", "PSP_010") son la única pista para distinguir un problema
 * de credenciales de un rechazo del banco.
 */
class IzipayApiException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly array $payload = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
    }

    public function errorCode(): ?string
    {
        $code = $this->payload['errorCode'] ?? data_get($this->payload, 'answer.errorCode');

        return is_string($code) && $code !== '' ? $code : null;
    }
}
