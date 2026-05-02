<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingEventSlot extends Model
{
    protected $fillable = [
        'event_id',
        'date',
        'start_time',
        'end_time',
        'is_booked'
    ];

    protected $casts = [
        'is_booked' => 'boolean'
    ];

    public function event()
    {
        return $this->belongsTo(MeetingEvent::class, 'event_id');
    }
}
