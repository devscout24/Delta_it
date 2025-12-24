<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MeetingBookingAvailabilities extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'is_available',
        'day',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Parent schedule for this availability
    public function schedule()
    {
        return $this->belongsTo(MeetingBookingSchedule::class, 'schedule_id');
    }

    // Slots for this availability (one availability has many slots)
    public function slots()
    {
        return $this->hasMany(MeetingBookingAvailabilitySlot::class, 'availability_id');
    }
}
