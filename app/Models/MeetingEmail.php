<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingEmail extends Model
{
    // Fillable
    protected $fillable = [
        'email', 'meeting_id'
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }
}
