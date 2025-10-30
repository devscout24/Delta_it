<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    protected $fillable = [
        'meeting_id',
        'email'
    ];
    protected $hidden = ['created_at', 'updated_at'];
}
