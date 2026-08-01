<?php

namespace App\Exceptions\Expedientes;

use RuntimeException;
use Throwable;

class PdfCompatibilityException extends RuntimeException
{
    public function __construct(
        public readonly string $reason,
        string $message,
        public readonly bool $canStoreOriginal = false,
        public readonly array $details = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
