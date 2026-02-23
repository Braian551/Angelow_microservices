<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception for resources not found.
 */
class NotFoundException extends Exception
{
    public function __construct(string $message = 'Recurso no encontrado', int $code = 404)
    {
        parent::__construct($message, $code);
    }
}
