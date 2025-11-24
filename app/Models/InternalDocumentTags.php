<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InternalDocumentTags extends Model
{
    protected $fillable = ['internal_document_id', 'tag'];

    public function document()
    {
        return $this->belongsTo(InternalDocument::class);
    }
}
