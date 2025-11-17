<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MeetingBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'company_id',
        'created_by',
        'booking_name',
        'booking_date',
        'booking_color',
        'online_link',
        'max_invitees',
        'description',
        'status',
        'auto_completed',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'auto_completed' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Linked Room
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    // Linked Company
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // User who created the booking config
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // All schedules for this meeting booking
    public function schedules()
    {
        return $this->hasMany(MeetingBookingSchedule::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Optional Helpers
    |--------------------------------------------------------------------------
    */

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }
}

