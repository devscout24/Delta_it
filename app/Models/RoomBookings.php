<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomBookings extends Model
{
    protected $fillable = [
        'booking_name',
        'company_id',
        'room_id',
        'start_date',
        'end_date',
        'add_emails',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'add_emails' => 'array',
    ];
}
