<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InternalDocumentFile extends Model
{
    protected $fillable = ['internal_document_id', 'file_path', 'file_type', 'file_name'];

    public function document()
    {
        return $this->belongsTo(InternalDocument::class);
    }
}
