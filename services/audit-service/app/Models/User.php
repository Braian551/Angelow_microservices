<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Modelo de usuario para el servicio de auditoría.
 *
 * Hereda de Authenticatable para compatibilidad con el sistema
 * de autenticación de Laravel (Sanctum/sessions). Aunque el
 * audit-service es principalmente de consulta, el modelo permite
 * que la base de usuarios compartida pueda autenticarse si
 * se requiere acceso administrativo al servicio.
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Atributos asignables masivamente (mass assignment).
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
     * Define los casts de tipos para los atributos del modelo.
     *
     * Convierte automáticamente `email_verified_at` a Carbon y
     * mantiene `password` siempre hasheado al asignarlo.
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
