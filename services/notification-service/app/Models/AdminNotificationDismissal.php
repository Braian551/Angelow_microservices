<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Lecturas persistidas del panel admin para notificaciones sintéticas.
 */
class AdminNotificationDismissal extends Model
{
    public $timestamps = false;

    protected $table = 'admin_notification_dismissals';

    protected $fillable = [
        'admin_id',
        'notification_key',
        'dismissed_at',
    ];

    protected function casts(): array
    {
        return [
            'dismissed_at' => 'datetime',
        ];
    }
}