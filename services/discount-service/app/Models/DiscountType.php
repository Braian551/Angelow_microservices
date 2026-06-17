<?php

namespace App\Models;

// Comentario de mantenimiento: Este modelo define la relación del dominio con su tabla y sus campos persistibles.

use Illuminate\Database\Eloquent\Model;

/**
 * Este modelo define la relación del dominio con su tabla y sus campos persistibles.
 */
class DiscountType extends Model
{
    protected $table = 'discount_types';

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}