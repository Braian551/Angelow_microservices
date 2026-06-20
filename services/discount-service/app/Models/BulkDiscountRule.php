<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa una regla de descuento por cantidad (volumen).
 * Ejemplo: "20% de descuento en compras de 3 a 5 unidades".
 * Se usa para aplicar descuentos automáticos según la cantidad de ítems en el carrito.
 */
class BulkDiscountRule extends Model
{
    protected $table = 'bulk_discount_rules';

    protected $fillable = [
        'min_quantity',       // Cantidad mínima de ítems para aplicar la regla
        'max_quantity',       // Cantidad máxima de ítems (null = sin límite superior)
        'discount_percentage', // Porcentaje de descuento a aplicar
        'is_active',           // Indica si la regla está vigente
    ];

    // Castings automáticos de tipos al acceder a los atributos desde Eloquent.
    protected $casts = [
        'min_quantity' => 'integer',       // Cantidad mínima como entero
        'max_quantity' => 'integer',       // Cantidad máxima como entero (null = sin límite)
        'discount_percentage' => 'float',  // Porcentaje de descuento como flotante
        'is_active' => 'boolean',          // Estado activo/inactivo de la regla
    ];
}