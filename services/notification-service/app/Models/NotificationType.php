<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tipo de notificacion del esquema legacy.
 */
class NotificationType extends Model
{
    protected $connection = 'legacy_mysql';

    protected $table = 'notification_types';

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
