<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierDocument extends Model
{
    protected $fillable = [
        'courier_profile_id', 'type', 'path', 'expires_at', 'status', 'review_note',
    ];

    protected function casts(): array
    {
        return ['expires_at' => 'date'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(CourierProfile::class, 'courier_profile_id');
    }
}
