<?php

namespace App\Models;

// Comentario de mantenimiento: Este modelo define la relación del dominio con su tabla y sus campos persistibles.

use Illuminate\Database\Eloquent\Model;

/**
 * Este modelo define la relación del dominio con su tabla y sus campos persistibles.
 */
class SiteSetting extends Model
{
    protected $table = 'site_settings';

    protected $fillable = [
        'setting_key',
        'setting_value',
        'category',
        'updated_by',
    ];

    public $timestamps = false;
}