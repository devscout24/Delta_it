<?php

namespace App\Models;

use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{

    protected $fillable = [
        'first_name',
        'last_name',
        'job_position',
        'email',
        'password',
    ];

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }
    protected $hidden = ['password', 'created_at', 'updated_at'];
}
