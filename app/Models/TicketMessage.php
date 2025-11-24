<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketMessage extends Model
{
    // fillable
    protected $guarded = [];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function files()
    {
        return $this->hasMany(TicketMessageFile::class);
    }
}
