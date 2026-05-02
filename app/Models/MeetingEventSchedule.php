<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingEventSchedule extends Model
{
    protected $fillable = [
        'meeting_event_id',
        'date'
    ];

    public function event()
    {
        return $this->belongsTo(MeetingEvent::class, 'meeting_event_id');
    }

    public function days()
    {
        return $this->hasMany(MeetingEventScheduleDay::class, 'schedule_id');
    }

    public function slots()
    {
        return $this->hasMany(MeetingEventSlot::class, 'meeting_event_schedule_id');
    }
}
