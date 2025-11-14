<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketMessageFile extends Model
{
    protected $table = 'ticket_message_files';

    protected $fillable = [
        'ticket_message_id',
        'file_path',
        'file_type',
        'file_size',
    ];

    // relationship back to message
    public function message()
    {
        return $this->belongsTo(TicketMessage::class, 'ticket_message_id');
    }

    // helper to get public URL
    public function getUrlAttribute()
    {
        return $this->file_path ? asset(ltrim($this->file_path, '/')) : null;
    }
}
