<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'room_name',
        'area',
        'position',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
