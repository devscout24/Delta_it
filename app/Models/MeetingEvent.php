<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingEvent extends Model
{
    protected $fillable = [
        'room_id',
        'company_id',
        'created_by',
        'event_name',
        'event_date',
        'event_color',
        'online_link',
        'max_invitees',
        'description',
        'status',
        'auto_completed',
    ];

    protected $casts = [
        'event_date' => 'date',
        'max_invitees' => 'integer',
        'auto_completed' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    // Belongs to Room
    public function room()
    {
        return $this->belongsTo(Room ::class);
    }

    // Belongs to Company
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Created by User
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
