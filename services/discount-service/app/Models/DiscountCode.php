<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa un código de descuento (cupón) del sistema.
 * Almacena el código, tipo de descuento (porcentaje o fijo), valor,
 * límites de uso, fechas de vigencia y restricciones por usuario.
 */
class DiscountCode extends Model
{
    protected $table = 'discount_codes';

    protected $fillable = [
        'code',             // Código alfanumérico del cupón (ej. PROMO10)
        'discount_type_id', // ID del tipo de descuento (porcentaje o fijo)
        'discount_value',    // Valor del descuento (porcentaje o monto fijo)
        'max_uses',          // Número máximo de usos totales (null = ilimitado)
        'used_count',        // Contador de usos actuales
        'start_date',        // Fecha de inicio de vigencia
        'end_date',          // Fecha de expiración
        'is_active',         // Indica si el cupón está habilitado
        'is_single_use',     // Indica si es de uso único por cliente
        'created_by',        // ID del administrador que lo creó
    ];

    // Castings automáticos de tipos al acceder a los atributos desde Eloquent.
    protected $casts = [
        'discount_type_id' => 'integer',  // ID del tipo de descuento como entero
        'discount_value' => 'float',      // Valor del descuento como flotante
        'max_uses' => 'integer',          // Máximo de usos como entero (null = ilimitado)
        'used_count' => 'integer',        // Contador de usos como entero
        'is_active' => 'boolean',         // Estado activo/inactivo
        'is_single_use' => 'boolean',     // Indicador de uso único por cliente
        'start_date' => 'datetime',       // Fecha de inicio como objeto Carbon
        'end_date' => 'datetime',         // Fecha de expiración como objeto Carbon
    ];

    /**
     * Relación con el tipo de descuento (DiscountType).
     * Permite acceder al nombre del tipo desde el código.
     */
    public function type()
    {
        return $this->belongsTo(DiscountType::class, 'discount_type_id');
    }
}