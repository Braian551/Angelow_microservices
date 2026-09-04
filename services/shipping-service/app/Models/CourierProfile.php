<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CourierProfile extends Model
{
    protected $fillable = [
        'user_id', 'email', 'document_type', 'document_number', 'document_number_hash',
        'birth_date', 'phone', 'address', 'status', 'rejection_reason', 'is_active',
        'terms_version', 'terms_accepted_at', 'reviewed_at', 'reviewed_by',
    ];

    protected $hidden = ['document_number_hash'];

    protected function casts(): array
    {
        return [
            'document_number' => 'encrypted',
            'birth_date' => 'date',
            'is_active' => 'boolean',
            'terms_accepted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function vehicle(): HasOne
    {
        return $this->hasOne(CourierVehicle::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CourierDocument::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(DeliveryAssignment::class);
    }
}
