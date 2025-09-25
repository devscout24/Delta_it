<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'banner_title',
        'banner_subtitle',
        'button_text',
        'button_url',
        'banner_image',
        'status'
    ];

    protected $casts = [
        'banner_title' => 'string',
        'banner_subtitle' => 'string',
        'button_text' => 'string',
        'button_url' => 'string',
        'banner_image' => 'string',
        'status' => 'boolean',
    ];
}
