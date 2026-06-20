<?php

namespace App\Models;

// Comentario de mantenimiento: Este modelo define la relación del dominio con su tabla y sus campos persistibles.

use Illuminate\Database\Eloquent\Model;

/**
 * Este modelo define la relación del dominio con su tabla y sus campos persistibles.
 */
class InventoryAlert extends Model
{
    protected $table = 'inventory_alerts';

    protected $fillable = [
        'variant_id',
        'product_id',
        'product_name',
        'color_name',
        'size_label',
        'sku',
        'stock',
        'status',
        'out_of_stock_since',
        'last_initial_notification_at',
        'last_reminder_at',
        'resolved_at',
    ];

    protected $casts = [
        'stock' => 'integer',
        'out_of_stock_since' => 'datetime',
        'last_initial_notification_at' => 'datetime',
        'last_reminder_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];
}