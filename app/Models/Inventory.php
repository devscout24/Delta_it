<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{

    protected $fillable = ['product_id', 'quantity', 'variation_id'];

    protected $casts = [
        'product_id' => 'integer',
        'quantity' => 'integer',
        'variation_id' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variation()
    {
        return $this->belongsTo(ProductVariation::class, 'variation_id');
    }


    protected $hidden = [
        'created_at',
        'updated_at',

    ];
}
