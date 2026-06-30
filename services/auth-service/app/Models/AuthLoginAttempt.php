<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de control agregado para intentos fallidos de acceso nativo.
 *
 * Mantiene un registro por combinación de credencial normalizada e IP para
 * decidir cuándo pedir verificación adicional o aplicar bloqueo temporal.
 */
class AuthLoginAttempt extends Model
{
    protected $fillable = [
        'credential',
        'ip_address',
        'failed_attempts',
        'last_failed_at',
        'blocked_until',
    ];

    protected $casts = [
        'failed_attempts' => 'integer',
        'last_failed_at' => 'datetime',
        'blocked_until' => 'datetime',
    ];
}
