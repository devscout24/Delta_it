<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InternalNote extends Model
{
    protected $fillable = [
        'title',
        'note'
    ];
    protected $hidden = ['created_at', 'updated_at'];
}
