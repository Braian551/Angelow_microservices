<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de códigos de recuperación de contraseña.
 *
 * Almacena cada solicitud de código de verificación (token hasheado)
 * con su expiración y estado de uso. No usa timestamps automáticos
 * de Laravel porque la tabla tiene created_at manual.
 */
class PasswordReset extends Model
{
    protected $table = 'password_resets';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
        'is_used',
        'trial554',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_used' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
