<?php

namespace App\Models;

// Comentario de mantenimiento: Este modelo define la relación del dominio con su tabla y sus campos persistibles.

use Illuminate\Database\Eloquent\Model;

/**
 * Este modelo define la relación del dominio con su tabla y sus campos persistibles.
 */
class Slider extends Model
{
    protected $table = 'sliders';

    protected $fillable = [
        'title',
        'subtitle',
        'image',
        'image_url',
        'link',
        'link_url',
        'order_position',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'order_position' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];
}