<?php

namespace App\Exceptions;

use Exception;

/**
 * Excepción personalizada para errores de autenticación.
 *
 * Proporciona respuestas de error estructuradas con códigos
 * HTTP apropiados (401, 403, 409, 422, 429, 500, etc.).
 * Usada por AuthService, PasswordRecoveryService y los
 * controladores para manejar errores del dominio de auth.
 */
class AuthException extends Exception
{
    public function __construct(
        string $message,
        int $code = 400,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
