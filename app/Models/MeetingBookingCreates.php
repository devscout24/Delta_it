<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingBookingCreates extends Model
{
    protected $fillable = [
        'user_id',
        'meeting_booking_id',
        'date',
        'start_time',
        'end_time',
        'invitees',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function meetingBooking()
    {
        return $this->belongsTo(MeetingBooking::class);
    }
}
