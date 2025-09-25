<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderBillingInfo extends Model
{
    // fillable

    protected $fillable = [
        'order_id',
        'name',
        'email',
        'phone',
        'address',
        'town',
        'state',
        'zipcode',
    ];
}
