<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingEventSchedule extends Model
{
    //fillable

    protected $fillable = [
        'meeting_event_id',
        'duration',
        'timezone',
        'schedule_mode',
        'future_days',
        'date_from',
        'date_to',
    ];

    // belongs to
    public function meeting_event()
    {
        return $this->belongsTo(MeetingEvent::class, 'meeting_event_id');
    }

    // has many
    public function availabilities()
    {
        return $this->hasMany(MeetingEventAvailabilities::class , 'schedule_id');
    }
}
