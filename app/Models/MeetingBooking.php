<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingBooking extends Model
{
    protected $fillable = [
        'event_id',
        'date',
        'start_time',
        'end_time',
        'name',
        'email',
        'status'
    ];

    public function event()
    {
        return $this->belongsTo(MeetingEvent::class, 'event_id');
    }
}
