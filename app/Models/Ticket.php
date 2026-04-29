<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'room_id',
        'subject',
        'type',
        'status',
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
}
