<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa los tipos de notificación disponibles en el sistema legacy.
 * Cada tipo (product, promotion, order) agrupa eventos similares
 * y permite a los usuarios configurar preferencias por categoría.
 */
class NotificationType extends Model
{
    /** Conexión a la base de datos legacy durante la migración. */
    protected $connection = 'legacy_mysql';

    protected $table = 'notification_types';

    protected $fillable = [
        'name',        // Nombre interno del tipo (product, promotion, order)
        'description', // Descripción legible para el usuario
        'is_active',   // Indica si el tipo está habilitado en el sistema
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
