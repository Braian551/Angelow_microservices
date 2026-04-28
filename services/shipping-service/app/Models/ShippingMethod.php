<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    protected $table = 'shipping_methods';

    protected $fillable = [
        'name',
        'description',
        'base_cost',
        'delivery_time',
        'estimated_days_min',
        'estimated_days_max',
        'free_shipping_threshold',
        'free_shipping_minimum',
        'available_cities',
        'city',
        'icon',
        'is_active',
    ];

    protected $casts = [
        'base_cost' => 'float',
        'free_shipping_threshold' => 'float',
        'free_shipping_minimum' => 'float',
        'estimated_days_min' => 'integer',
        'estimated_days_max' => 'integer',
        'is_active' => 'boolean',
    ];
}