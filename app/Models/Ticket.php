<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'unique_id',
        'company_id',
        'user_id',
        'requester_id',
        'requester_role',
        'room_id',
        'subject',
        'type',
        'status',
        'date',
        'action',
    ];

    public function messages()
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }
}
