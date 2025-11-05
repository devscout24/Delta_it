<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CUser extends Model
{
    protected $fillable = [
        'name',
        'company',
        'email',
        'role',
        'password',
    ];
    protected $hidden = ['password', 'created_at', 'updated_at'];



    protected function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }
}
