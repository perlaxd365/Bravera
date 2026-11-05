<?php

namespace App\Modules\Mcp\Exceptions;

use RuntimeException;

class McpToolException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        string $message,
        public readonly array $details = [],
    ) {
        parent::__construct($message);
    }
}
