<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collaborator extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'job_position',
        'email',
        'phone_extension',
        'phone_number',
        'access_card_number',
        'parking_card',
    ];

    protected $hidden = ['created_at', 'updated_at'];
}
