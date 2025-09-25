<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'category_sub_title',
        'category_image'
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }


    protected $casts = [
        'name' => 'string',
        'slug' => 'string',
        'category_sub_title' => 'string',
        'category_image' => 'string',
    ];
}
