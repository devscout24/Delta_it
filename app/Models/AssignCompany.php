<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignCompany extends Model
{
    protected $fillable = [
        'room_id',
        'company_id'
    ];

    protected $hidden = ['created_at', 'updated_at'];
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
