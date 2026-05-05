<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingEventSlot extends Model
{
    protected $fillable = [
        'meeting_event_schedule_id',
        'event_id',
        'date',
        'start_time',
        'end_time',
        'is_booked',
    ];

    protected $casts = [
        'is_booked' => 'boolean'
    ];

    // event access via schedule->event when needed

    public function schedule()
    {
        return $this->belongsTo(MeetingEventSchedule::class, 'meeting_event_schedule_id');
    }
}
