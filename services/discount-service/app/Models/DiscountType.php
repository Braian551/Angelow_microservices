<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountType extends Model
{
    protected $table = 'discount_types';

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}