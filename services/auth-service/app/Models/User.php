<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Modelo de usuario del sistema Angelow.
 *
 * Refleja el esquema de la tabla legacy `users` para compatibilidad
 * durante la migración. Usa IDs alfanuméricos (string) en lugar de
 * autoincrementales, heredados del sistema legacy basado en uniqid().
 * Implementa autenticación vía Sanctum (HasApiTokens).
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Indica que los IDs NO son auto-incrementales (son string tipo uniqid).
     */
    public $incrementing = false;

    /**
     * Tipo de dato de la llave primaria (string para compatibilidad legacy).
     */
    protected $keyType = 'string';

    /**
     * Atributos asignables masivamente (mass assignment).
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'phone',
        'password',
        'image',
        'role',
        'is_blocked',
    ];

    /**
     * Atributos ocultos en serialización (JSON).
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts de tipos para atributos del modelo.
     *
     * - password: se hashea automáticamente al asignarlo
     * - is_blocked: se convierte a booleano
     * - created_at, updated_at, last_access, token_expiry: Carbon dates
     */
    protected function casts(): array
    {
        return [
            'password'    => 'hashed',
            'is_blocked'  => 'boolean',
            'created_at'  => 'datetime',
            'updated_at'  => 'datetime',
            'last_access'  => 'datetime',
            'token_expiry' => 'datetime',
        ];
    }

    /**
     * Verifica si el usuario tiene rol de administrador.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Verifica si el usuario está bloqueado.
     */
    public function isBlocked(): bool
    {
        return $this->is_blocked;
    }
}
