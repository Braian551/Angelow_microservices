<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa una reserva de stock en la base de datos.
 *
 * Gestiona el tiempo de vida de las reservas de inventario asociadas
 * a órdenes de compra, incluyendo su expiración y liberación.
 */
class StockReservation extends Model
{
    protected $table = 'stock_reservations';

    protected $fillable = [
        'order_id',
        'product_id',
        'size_variant_id',
        'reservation_key',
        'quantity',
        'status',
        'expires_at',
        'confirmed_at',
        'released_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'released_at' => 'datetime',
    ];
}
