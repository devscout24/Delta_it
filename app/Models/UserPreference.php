<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'travel_distance',
        'preferred_weather',
        'companion_type',
        'spending_comfort',
        'preferred_time',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
