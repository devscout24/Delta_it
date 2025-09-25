<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        "product_name",
        "description",
        "chart",
        "shipping",
        "product_code",
        "category_id",
        "max_capacity",
        "eqt",
        "condition",
        "location",
        "regular_price",
        "discount_price",
        "quantity",
        "status",
        "featured_image"
    ];


    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function galleries()
    {
        return $this->hasMany(ImageGallery::class);
    }


    protected $casts = [
        'category_id'              => 'integer',
        'product_name'             => 'string',
        'slug'                     => 'string',
        'product_code'             => 'string',
        'brand'                    => 'string',
        'product_type'             => 'string',
        'product_version_type'     => 'string',
        'short_description'        => 'string',
        'description'              => 'string',
        'additional_description'   => 'string',
        'regular_price'            => 'float',
        'discount_price'           => 'float',
        'weight'                   => 'float',
        'weight_unit'              => 'string',
        'status'                   => 'integer',
        'product_image'            => 'string',

    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'status',
    ];
}
