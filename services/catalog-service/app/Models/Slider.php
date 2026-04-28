<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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