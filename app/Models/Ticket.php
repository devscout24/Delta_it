<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function assignedToUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
