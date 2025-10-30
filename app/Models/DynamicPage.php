<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DynamicPage extends Model
{
    protected $fillable = ['slug', 'title', 'content', 'status'];
    protected $hidden = ['created_at', 'updated_at'];
}
