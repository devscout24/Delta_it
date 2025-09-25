<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItemDetail extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'color_id',
        'size_id',
        'quantity',
        'price',
        'item_total',
    ];


    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }


    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'product_id', 'product_id');
    }
    // public function order()
    // {
    //     return $this->belongsTo(Order::class);
    // }
    protected $hidden = [
        'created_at',
        'updated_at',

    ];
}
