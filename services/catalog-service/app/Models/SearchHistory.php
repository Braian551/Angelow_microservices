<?php

namespace App\Models;

// Comentario de mantenimiento: Este modelo define la relación del dominio con su tabla y sus campos persistibles.

use Illuminate\Database\Eloquent\Model;

/**
 * Este modelo define la relación del dominio con su tabla y sus campos persistibles.
 */
class SearchHistory extends Model
{
    protected $table = 'search_history';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'search_term',
        'created_at',
    ];
}