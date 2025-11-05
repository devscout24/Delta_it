<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomAppointment extends Model
{
    protected $fillable = [
        'event_name',
        'room_id',
        'max_invitees',
        'event_color',
        'description',
        'duration',
        'timezone',
    ];
    protected $hidden = ['created_at', 'updated_at'];
}
