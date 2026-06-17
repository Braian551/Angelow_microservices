<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que almacena las preferencias de notificación de cada usuario
 * sobre la tabla legacy `notification_preferences`.
 * Controla qué canales (email, SMS, push) están habilitados por tipo de evento.
 */
class NotificationPreference extends Model
{
    /** Conexión a la base de datos legacy durante la migración. */
    protected $connection = 'legacy_mysql';

    protected $table = 'notification_preferences';

    protected $fillable = [
        'user_id',       // ID del usuario en el sistema legacy
        'type_id',       // ID del tipo de notificación (product, promotion, order)
        'email_enabled', // Indica si el usuario acepta correos para este tipo
        'sms_enabled',   // Indica si el usuario acepta SMS para este tipo
        'push_enabled',  // Indica si el usuario acepta notificaciones push para este tipo
    ];

    protected function casts(): array
    {
        return [
            'email_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
            'push_enabled' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
