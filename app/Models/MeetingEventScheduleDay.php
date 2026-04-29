<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingEventScheduleDay extends Model
{
    protected $fillable = [
        'schedule_id',
        'day_of_week',
        'start_time',
        'end_time'
    ];

    public function schedule()
    {
        return $this->belongsTo(MeetingEventSchedule::class, 'schedule_id');
    }
}
