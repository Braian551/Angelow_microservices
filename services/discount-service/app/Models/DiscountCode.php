<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountCode extends Model
{
    protected $table = 'discount_codes';

    protected $fillable = [
        'code',
        'discount_type_id',
        'discount_value',
        'max_uses',
        'used_count',
        'start_date',
        'end_date',
        'is_active',
        'is_single_use',
        'created_by',
    ];

    protected $casts = [
        'discount_type_id' => 'integer',
        'discount_value' => 'float',
        'max_uses' => 'integer',
        'used_count' => 'integer',
        'is_active' => 'boolean',
        'is_single_use' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function type()
    {
        return $this->belongsTo(DiscountType::class, 'discount_type_id');
    }
}