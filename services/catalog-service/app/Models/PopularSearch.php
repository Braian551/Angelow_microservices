<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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