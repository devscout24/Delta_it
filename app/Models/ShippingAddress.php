<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingAddress extends Model
{
    protected $fillable = [
        'user_id',
        'user_id',
        'first_name',
        'last_name',
        'address',
        'town',
        'state',
        'zipcode',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',

    ];
}
