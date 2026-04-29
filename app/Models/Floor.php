<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Floor extends Model
{
    protected $fillable = [
        'name',
        'level',
    ];

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}
