<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketMessage extends Model
{
    // fillable
    protected $fillable = [
        'ticket_id',
        'user_id',
        'message',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
