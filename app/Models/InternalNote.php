<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InternalNote extends Model
{
    protected $fillable = [
        'company_id',
        'title',
        'note'
    ];
    protected $hidden = ['created_at', 'updated_at'];
}
