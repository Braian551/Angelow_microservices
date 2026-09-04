<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierLocation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'delivery_assignment_id', 'latitude', 'longitude', 'heading',
        'speed', 'accuracy', 'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float', 'longitude' => 'float', 'heading' => 'float',
            'speed' => 'float', 'accuracy' => 'float', 'recorded_at' => 'datetime',
        ];
    }
}
