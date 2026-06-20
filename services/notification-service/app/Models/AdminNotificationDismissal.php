<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que registra las notificaciones sintéticas que un administrador
 * ha descartado (leído/cerrado) en el panel de administración.
 * Cada registro indica qué notificación (clave) fue descartada y cuándo.
 */
class AdminNotificationDismissal extends Model
{
    /** Desactiva timestamps automáticos porque usamos dismissed_at manualmente. */
    public $timestamps = false;

    protected $table = 'admin_notification_dismissals';

    protected $fillable = [
        'admin_id',         // ID del administrador que descartó la notificación
        'notification_key', // Clave única que identifica la notificación sintética
        'dismissed_at',     // Marca de tiempo del momento en que se descartó
    ];

    protected function casts(): array
    {
        return [
            'dismissed_at' => 'datetime',
        ];
    }
}