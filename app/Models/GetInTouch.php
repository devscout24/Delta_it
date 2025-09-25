<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GetInTouch extends Model
{
    protected $this = [
        'full_name',
        'subject',
        'email_address',
        'comment',
    ];

    protected $casts = [
        'full_name' => 'string',
        'subject' => 'string',
        'email_address' => 'string',
        'comment' => 'string',
    ];
}
