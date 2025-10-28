<?php

namespace App\Models;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

class MettingBookRequest extends Model
{
    protected $fillable = [
        'room_id',
        'company_id',
        'date',
        'start_time',
        'end_time',
        'status',
    ];
}
