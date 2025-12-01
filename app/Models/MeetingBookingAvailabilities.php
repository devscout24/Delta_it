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

    // This references the "slot" model, even though your schema incorrectly
    // references the same table. We assume intended relationship:
    public function slot()
    {
        return $this->belongsTo(MeetingBookingAvailabilitySlot::class, 'availability_id');
    }
}
