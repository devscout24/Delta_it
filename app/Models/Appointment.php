<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'meeting_id',
        'room_id',
        "online_link",
        'max_invitees',
        'event_color',
        'description',
        'duration',
        'timezone',
    ];

    function room(){
        return $this->belongsTo(Room::class,'room_id');
    }

    function meeting(){
        return $this->belongsTo(Meeting::class,'meeting_id');
    }

    protected $hidden = ['created_at', 'updated_at'];
}
