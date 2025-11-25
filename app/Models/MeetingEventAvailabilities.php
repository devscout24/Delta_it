<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingEventAvailabilities extends Model
{
    // fillable
    protected $fillable = [
        'schedule_id',
        'day',
        'is_available',
    ];

    // belongs to
    public function schedule()
    {
        return $this->belongsTo(MeetingEventSchedule::class, 'schedule_id');
    }

    // has many
    public function slots()
    {
        return $this->hasMany(MeetingEventAvailabilitySlot::class, 'availability_id');
    }
}
