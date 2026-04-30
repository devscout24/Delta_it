<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentTag extends Model
{
    public function documents()
    {
        return $this->belongsToMany(Document::class, 'document_tag');
    }
}
