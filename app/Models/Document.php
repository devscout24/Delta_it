<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'company_id',
        'uploaded_by',
        'name',
        'file_path',
        'type',
        'visibility',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
    public function tags()
    {
        return $this->belongsToMany(\App\Models\Tag::class, 'document_tag');
    }
}
