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
        'recipient_name',
        'phone',
        'address_line_1',
        'address_line_2',
        'city',
        'department',
        'postal_code',
        'country',
        'notes',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}