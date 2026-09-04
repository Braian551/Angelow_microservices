<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryAssignment extends Model
{
    protected $fillable = [
        'order_id', 'order_number', 'order_source', 'courier_profile_id',
        'customer_user_id', 'customer_email', 'shipping_method_id',
        'shipping_method_name', 'delivery_time', 'destination_address',
        'destination_city', 'destination_latitude', 'destination_longitude',
        'status', 'delivery_code_hash', 'delivery_code', 'sharing_location', 'accepted_at',
        'route_started_at', 'arrived_at', 'delivered_at',
    ];

    protected $hidden = ['delivery_code_hash', 'delivery_code'];

    protected function casts(): array
    {
        return [
            'sharing_location' => 'boolean',
            'delivery_code' => 'encrypted',
            'destination_latitude' => 'float',
            'destination_longitude' => 'float',
            'accepted_at' => 'datetime',
            'route_started_at' => 'datetime',
            'arrived_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(CourierProfile::class, 'courier_profile_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(CourierLocation::class);
    }
}
