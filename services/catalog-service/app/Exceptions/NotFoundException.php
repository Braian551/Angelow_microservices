<?php

namespace App\Exceptions;

// Comentario de mantenimiento: Esta excepción normaliza errores de dominio para respuestas consistentes.

use Exception;

/**
 * Exception for resources not found.
 */
class NotFoundException extends Exception
{
    /**
     * Explica la intención de __construct dentro del flujo del servicio.
     */
    public function __construct(string $message = 'Recurso no encontrado', int $code = 404)
    {
        parent::__construct($message, $code);
    }
}
