<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingEventAvailabilitySlot extends Model
{
    // fillable
    protected $fillable = [
        'availability_id',
        'start_time',
        'end_time',
    ];

    // belongs to
    public function availability()
    {
        return $this->belongsTo(MeetingEventAvailabilities::class, 'availability_id');
    }
}
