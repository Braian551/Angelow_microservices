<?php

namespace App\Models;

// Comentario de mantenimiento: Este modelo define la relación del dominio con su tabla y sus campos persistibles.

use Illuminate\Database\Eloquent\Model;

/**
 * Este modelo define la relación del dominio con su tabla y sus campos persistibles.
 */
class DiscountCode extends Model
{
    protected $table = 'discount_codes';

    protected $fillable = [
        'code',
        'discount_type_id',
        'discount_value',
        'max_uses',
        'used_count',
        'start_date',
        'end_date',
        'is_active',
        'is_single_use',
        'created_by',
    ];

    protected $casts = [
        'discount_type_id' => 'integer',
        'discount_value' => 'float',
        'max_uses' => 'integer',
        'used_count' => 'integer',
        'is_active' => 'boolean',
        'is_single_use' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    /**
     * Explica la intención de type dentro del flujo del servicio.
     */

    public function type()
    {
        return $this->belongsTo(DiscountType::class, 'discount_type_id');
    }
}