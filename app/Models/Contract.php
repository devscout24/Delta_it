<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    protected $fillable = [
        'name',
        'type',
        'start_date',
        'end_date',
        'renewal_date',
        'status',
        'company_id'
    ];


    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    protected $hidden = ['created_at', 'updated_at'];
}
