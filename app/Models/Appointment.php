<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'meeting_id',
        'room_id',
        'max_invitees',
        'start_date',
        'end_date',
        'event_color',
        'description',
        'invitees_select',
        'duration',
        'timezone',
    ];

    protected $hidden = ['created_at', 'updated_at'];
}
