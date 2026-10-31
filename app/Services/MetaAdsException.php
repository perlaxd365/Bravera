<?php

namespace App\Services;

use RuntimeException;

class MetaAdsException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        string $message,
        public readonly int $httpStatus = 0,
        public readonly array $payload = [],
    ) {
        parent::__construct($message, $httpStatus);
    }
}
