<?php

namespace App\Exceptions;

use Exception;

/**
 * Custom exception for authentication-related errors.
 *
 * Provides structured error responses with appropriate HTTP status codes.
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
