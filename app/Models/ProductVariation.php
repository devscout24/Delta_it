<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariation extends Model
{
    protected $fillable = [
        'color',
        'size',
    ];

    protected $casts = [
        'color' => 'string',
        'size' => 'integer',
    ];

    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'variation_id');
    }
}
