<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomAppointmentSlot extends Model
{
    protected $fillable = [
        'appointment_id',
        'weekly_schedule_id',
        'meeting_id',
        'day',
        'start_time',
        'end_time',
        'availability_status',
    ];


    protected $hidden = ['created_at', 'updated_at'];
}
