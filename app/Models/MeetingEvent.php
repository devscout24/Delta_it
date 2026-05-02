<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingEvent extends Model
{
    protected $fillable = [
        'title',
        'type',
        'location',
        'meeting_link',
        'duration',
        'max_invitees',
        'description',
        'color',
        'timezone'
    ];

    // ======================
    // RELATIONS
    // ======================

    public function schedules()
    {
        return $this->hasMany(MeetingEventSchedule::class);
    }

    public function slots()
    {
        return $this->hasManyThrough(
            MeetingEventSlot::class,
            MeetingEventSchedule::class,
            'meeting_event_id', // Foreign key on schedules table...
            'meeting_event_schedule_id', // Foreign key on slots table...
            'id', // Local key on events
            'id' // Local key on schedules
        );
    }

    public function bookings()
    {
        return $this->hasMany(MeetingBooking::class, 'event_id');
    }
}
