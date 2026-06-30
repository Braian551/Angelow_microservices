<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Modelo de usuario para shipping-service (uso interno/admin).
 *
 * Este modelo es el estándar de Laravel para autenticación local.
 * En el contexto de microservicios, la autenticación principal se
 * maneja desde auth-service. Este modelo existe para:
 * - Soporte de pruebas unitarias y factories.
 * - Posible autenticación interna para herramientas de administración.
 * - Compatibilidad con el scaffolding predeterminado de Laravel.
 *
 * No se usa para la autenticación real del frontend SPA, que
 * depende de auth-service con tokens JWT.
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Atributos asignables de forma masiva.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Atributos ocultos en serialización (JSON).
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Conversión de tipos nativos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
