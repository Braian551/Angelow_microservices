<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail; // Interfaz de verificación de email (actualmente sin uso)
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Modelo de usuario para el servicio de descuentos.
 * Extiende Authenticatable para soporte de autenticación local si es necesario,
 * aunque la autenticación real se delega al auth-service.
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Atributos asignables masivamente mediante creación/actualización.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',     // Nombre completo del usuario
        'email',    // Correo electrónico del usuario
        'password', // Contraseña hasheada del usuario
    ];

    /**
     * Atributos ocultos en serialización JSON para proteger datos sensibles.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',       // Contraseña: nunca se debe exponer en respuestas JSON
        'remember_token', // Token de sesión persistente: oculto por seguridad
    ];

    /**
     * Castings automáticos de tipos nativos al acceder a los atributos desde Eloquent.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime', // Fecha de verificación como objeto Carbon
            'password' => 'hashed',             // Contraseña hasheada automáticamente
        ];
    }
}
