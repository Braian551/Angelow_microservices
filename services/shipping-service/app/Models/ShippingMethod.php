<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo ORM para métodos de envío (tabla shipping_methods).
 *
 * Define las opciones de envío disponibles: nombre, descripción, costo base,
 * tiempo estimado de entrega, umbral de envío gratis, ciudades disponibles
 * e indicador de actividad. Es la tabla destino de la migración desde legacy.
 *
 * @property float $base_cost Costo base del método antes de aplicar reglas
 * @property float|null $free_shipping_minimum Monto mínimo para envío gratis
 * @property int|null $estimated_days_min Mínimo de días estimados de entrega
 * @property int|null $estimated_days_max Máximo de días estimados de entrega
 */
class ShippingMethod extends Model
{
    /** Tabla asociada en shipping-db */
    protected $table = 'shipping_methods';

    /** Campos asignables de forma masiva */
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

    /** Conversión de tipos nativos al acceder a atributos */
    protected $casts = [
        'base_cost' => 'float',
        'free_shipping_threshold' => 'float',
        'free_shipping_minimum' => 'float',
        'estimated_days_min' => 'integer',
        'estimated_days_max' => 'integer',
        'is_active' => 'boolean',
    ];
}