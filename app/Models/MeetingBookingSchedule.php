<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MeetingBookingSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_booking_id',
        'duration',
        'timezone',
        'schedule_mode',
        'future_days',
        'date_from',
        'date_to',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Parent meeting booking
    public function meetingBooking()
    {
        return $this->belongsTo(MeetingBooking::class);
    }

    // All availabilities for this schedule (Mon–Sun)
    public function availabilities()
    {
        return $this->hasMany(MeetingBookingAvailabilities::class, 'schedule_id');
    }

    // Convenience: all slots across availabilities
    public function availabilitySlots()
    {
        return $this->hasManyThrough(
            MeetingBookingAvailabilitySlot::class,
            MeetingBookingAvailabilities::class,
            'schedule_id',
            'availability_id',
            'id',
            'id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Optional Helpers
    |--------------------------------------------------------------------------
    */

    public function isFutureDaysMode()
    {
        return $this->schedule_mode === 'future_days';
    }

    public function isDateRangeMode()
    {
        return $this->schedule_mode === 'date_range';
    }
}
