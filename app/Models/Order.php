<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'order_number',
        'payment_status',
        'status',
        'payment_method',
        'price',
        'total_amount',
        'discount_amount',
        'shipping_cost',
        'sub_total',
        'coupon_code',
        'is_paid',
        'cancel_reason',
        'placed_at',
        'delivered_at',
    ];




    public function user()
    {
        return $this->belongsTo(User::class);
    }



    public function items()
    {
        return $this->hasMany(OrderItemDetail::class, 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function billing()
    {
        return $this->hasOne(OrderBillingInfo::class, 'order_id');
    }
    protected $hidden = [
        'created_at',
        'updated_at',

    ];
}
