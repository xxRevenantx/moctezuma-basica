<?php

namespace App\Exceptions;

use RuntimeException;

class PeriodoImportException extends RuntimeException
{
    /**
     * @param  array<int, string>  $errores
     */
    public function __construct(
        private readonly array $errores,
        string $message = 'La plantilla contiene errores de validación.'
    ) {
        parent::__construct($message);
    }

    /**
     * @return array<int, string>
     */
    public function errores(): array
    {
        return $this->errores;
    }
}
