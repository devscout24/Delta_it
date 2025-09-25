<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $fillable = [
        'offer_percent',
        'offer_name',
        'offered_by_product_id',
        'offer_start_date',
        'offer_end_date',
        'image',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'offered_by_product_id');
    }


    protected $casts = [
        'offer_percent' => 'integer',
        'offer_name' => 'string',
        'offer_start_date' => 'date',
        'offer_end_date' => 'date',
        'image' => 'string',
    ];
}
