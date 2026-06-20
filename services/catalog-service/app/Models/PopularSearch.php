<?php

namespace App\Models;

// Comentario de mantenimiento: Este modelo define la relación del dominio con su tabla y sus campos persistibles.

use Illuminate\Database\Eloquent\Model;

/**
 * Este modelo define la relación del dominio con su tabla y sus campos persistibles.
 */
class PopularSearch extends Model
{
    protected $table = 'popular_searches';

    public $timestamps = false;

    protected $fillable = [
        'search_term',
        'search_count',
        'last_searched',
    ];
}