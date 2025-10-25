<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'document_path',
        'document_type',
        'document_name'
    ];

    protected $hidden = ['created_at', 'updated_at'];
}
