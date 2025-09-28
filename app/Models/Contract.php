<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $fillable = [
        'name',
        'type',
        'start_date',
        'end_date',
        'renewal_date',
        'status',
        'company_id'
    ];
}
