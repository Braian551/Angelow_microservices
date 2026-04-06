<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BulkDiscountRule extends Model
{
    protected $table = 'bulk_discount_rules';

    protected $fillable = [
        'min_quantity',
        'max_quantity',
        'discount_percentage',
        'is_active',
    ];

    protected $casts = [
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'discount_percentage' => 'float',
        'is_active' => 'boolean',
    ];
}