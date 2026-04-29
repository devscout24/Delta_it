<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingBooking extends Model
{
    public function event()
    {
        return $this->belongsTo(MeetingEvent::class, 'meeting_event_id');
    }

    public function slot()
    {
        return $this->belongsTo(MeetingEventSlot::class, 'meeting_event_slot_id');
    }
}
