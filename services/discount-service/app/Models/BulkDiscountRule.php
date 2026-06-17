<?php

namespace App\Models;

// Comentario de mantenimiento: Este modelo define la relación del dominio con su tabla y sus campos persistibles.

use Illuminate\Database\Eloquent\Model;

/**
 * Este modelo define la relación del dominio con su tabla y sus campos persistibles.
 */
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