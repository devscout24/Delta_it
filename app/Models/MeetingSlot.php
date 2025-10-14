<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingSlot extends Model
{
    protected $fillable = [
        'day_id',
        'start_time',
        'end_time',
        'date',
        'meeting_id',
    ];
}
