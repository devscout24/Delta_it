<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImageGallery extends Model
{

    protected $fillable = [
        'product_id',
        'image_path',
    ];


    protected $casts = [
        'product_id' => 'integer',
        'image_path' => 'string',
    ];



    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
