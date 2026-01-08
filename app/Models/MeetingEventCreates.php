<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingEventCreates extends Model
{
    protected $fillable = [
        'user_id',
        'meeting_event_id',
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

    public function meetingEvent()
    {
        return $this->belongsTo(MeetingEvent::class);
    }
}
