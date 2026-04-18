<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de direcciones de envío por usuario.
 */
class UserAddress extends Model
{
    protected $table = 'user_addresses';

    protected $fillable = [
        'user_id',
        'address_type',
        'alias',
        'recipient_name',
        'recipient_phone',
        'address',
        'complement',
        'neighborhood',
        'building_type',
        'building_name',
        'apartment_number',
        'delivery_instructions',
        'is_default',
        'is_active',
        'gps_latitude',
        'gps_longitude',
        'gps_accuracy',
        'gps_timestamp',
        'gps_used',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'gps_used' => 'boolean',
            'gps_latitude' => 'float',
            'gps_longitude' => 'float',
            'gps_accuracy' => 'float',
            'gps_timestamp' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}