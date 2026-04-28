<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingPriceRule extends Model
{
    protected $table = 'shipping_price_rules';

    protected $fillable = [
        'min_price',
        'max_price',
        'shipping_cost',
        'is_active',
    ];

    protected $casts = [
        'min_price' => 'float',
        'max_price' => 'float',
        'shipping_cost' => 'float',
        'is_active' => 'boolean',
    ];
}