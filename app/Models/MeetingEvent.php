<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingEvent extends Model
{
    protected $fillable = [
        'user_id',
        'event_name',
        'location',
        'color',
        'meeting_link',
        'max_invitees',
        'description',
        'duration',
        'timezone',
        'start_date',
        'end_date'
    ];

    public function schedules()
    {
        return $this->hasMany(WeeklySchedule::class);
    }

    public function bookings()
    {
        return $this->hasMany(MeetingBookings::class);
    }
}
