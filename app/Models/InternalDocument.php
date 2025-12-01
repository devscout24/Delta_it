<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InternalDocument extends Model
{
    protected $fillable = ['name','type', 'company_id'];

    public function files()
    {
        return $this->hasMany(InternalDocumentFile::class);
    }

    public function tags()
    {
        return $this->hasMany(InternalDocumentTags::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
