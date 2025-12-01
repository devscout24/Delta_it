<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MeetingBookingAvailabilitySlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'availability_id',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Belongs to schedule
    public function schedule()
    {
        return $this->belongsTo(MeetingBookingSchedule::class, 'schedule_id');
    }

    // Time ranges (start_time / end_time)
    public function timeRanges()
    {
        return $this->hasMany(MeetingBookingAvailabilities::class, 'availability_id');
    }
}
