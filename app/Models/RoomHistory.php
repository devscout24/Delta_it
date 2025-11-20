<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomHistory extends Model
{
    // Fillable
    protected $fillable = [
        'room_id',
        'company_id',
        'date',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // Relationships
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
