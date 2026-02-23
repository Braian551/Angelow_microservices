<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * User Model
 *
 * Represents a user in the Angelow platform.
 * Mirrors the legacy users table schema for migration compatibility.
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The data type of the primary key.
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'phone',
        'password',
        'image',
        'role',
        'is_blocked',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'password'    => 'hashed',
            'is_blocked'  => 'boolean',
            'created_at'  => 'datetime',
            'updated_at'  => 'datetime',
            'last_access'  => 'datetime',
            'token_expiry' => 'datetime',
        ];
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if the user is blocked.
     */
    public function isBlocked(): bool
    {
        return $this->is_blocked;
    }
}
