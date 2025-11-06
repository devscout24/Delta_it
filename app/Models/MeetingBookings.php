<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingBookings extends Model
{
    protected $fillable = ['meeting_event_id', 'date', 'slot_start', 'slot_end', 'user_id'];
}
