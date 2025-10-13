<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    protected $fillable = [
        'meeting_name',
        'date',
        'start_time',
        'end_time',
        'room_id',
        'meeting_type',
        'online_link'
    ];
}
