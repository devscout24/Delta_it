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

    // Belongs to availability
    public function availability()
    {
        return $this->belongsTo(MeetingBookingAvailabilities::class, 'availability_id');
    }
}
