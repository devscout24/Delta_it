<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpaceBooking extends Model
{
    protected $fillable = [
        'space_id',
        'user_id',
        'company_id',
        'date',
        'start_time',
        'end_time',
        'status',
    ];

    public function space()
    {
        return $this->belongsTo(Space::class, 'space_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
