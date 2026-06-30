<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo ORM para reglas de precio por rango (tabla shipping_price_rules).
 *
 * Define costos de envío adicionales basados en el subtotal del carrito.
 * Ejemplo de regla: "De $0 a $50.000 → $9.900 de costo de envío".
 * Cuando el subtotal está dentro del rango, el costo definido se suma
 * al costo base del método de envío seleccionado.
 *
 * @property float $min_price Límite inferior del rango de precio
 * @property float|null $max_price Límite superior del rango (null = sin tope)
 * @property float $shipping_cost Costo adicional de envío para este rango
 * @property bool $is_active Indica si la regla está vigente
 */
class ShippingPriceRule extends Model
{
    /** Tabla asociada en shipping-db */
    protected $table = 'shipping_price_rules';

    /** Campos asignables de forma masiva */
    protected $fillable = [
        'min_price',
        'max_price',
        'shipping_cost',
        'is_active',
    ];

    /** Conversión de tipos nativos */
    protected $casts = [
        'min_price' => 'float',
        'max_price' => 'float',
        'shipping_cost' => 'float',
        'is_active' => 'boolean',
    ];
}