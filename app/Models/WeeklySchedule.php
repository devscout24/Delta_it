<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeeklySchedule extends Model
{
    protected $fillable =
    [
        'appointment_id',
        'meeting_id',
        'day',
        'start_time',
        'end_time',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
