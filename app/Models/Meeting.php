<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    protected $fillable = [
        'meeting_name',
        'date',
        'start_time',
        'end_time',
        'room_id',
        'meeting_type',
        'online_link',
        'created_by',
        'status',
        'company_id',
        'add_emails',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function appointmentSlots()
    {
        return $this->hasMany(AppointmentSlot::class, 'meeting_id');
    }


    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
