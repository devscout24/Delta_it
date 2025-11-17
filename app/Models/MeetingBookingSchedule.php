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

    // Availability slots for Mon–Sun
    public function availabilitySlots()
    {
        return $this->hasMany(MeetingBookingAvailabilitySlot::class, 'schedule_id');
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
