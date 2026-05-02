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
        return $this->hasMany(MeetingEventSlot::class, 'event_id');
    }

    public function bookings()
    {
        return $this->hasMany(MeetingBooking::class, 'event_id');
    }
}
