<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierVehicle extends Model
{
    protected $fillable = [
        'courier_profile_id', 'type', 'make_id', 'make_name', 'model_id',
        'model_name', 'color_name', 'color_hex', 'year', 'plate', 'ownership_type',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(CourierProfile::class, 'courier_profile_id');
    }
}
