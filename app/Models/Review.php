<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'name',
        'profession',
        'photo',
        'review_content',
        'rating_point',
    ];


    protected $casts = [
        'review_content' => 'string',
        'rating_point' => 'float',
    ];
}
