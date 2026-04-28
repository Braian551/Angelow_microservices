<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Preferencias de notificacion por usuario en esquema legacy.
 */
class NotificationPreference extends Model
{
    protected $connection = 'legacy_mysql';

    protected $table = 'notification_preferences';

    protected $fillable = [
        'user_id',
        'type_id',
        'email_enabled',
        'sms_enabled',
        'push_enabled',
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
