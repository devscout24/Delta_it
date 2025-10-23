<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentSlot extends Model
{
    protected $fillable = [
        'appointment_id',
        'weekly_schedule_id',
        'meeting_id',
        'start_time',
        'day',
        'end_time'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
