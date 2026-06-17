<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa los tipos de descuento disponibles en el sistema.
 * Ejemplos: 'percentage' (porcentual), 'fixed_amount' (monto fijo).
 * Se relaciona con DiscountCode para categorizar cada cupón.
 */
class DiscountType extends Model
{
    protected $table = 'discount_types';

    protected $fillable = [
        'name',        // Nombre interno del tipo (percentage, fixed_amount)
        'description', // Descripción legible del tipo de descuento
        'is_active',   // Indica si el tipo está habilitado
    ];

    // Convierte is_active a booleano automáticamente al acceder al atributo.
    protected $casts = [
        'is_active' => 'boolean',
    ];
}