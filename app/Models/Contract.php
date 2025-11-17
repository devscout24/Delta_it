<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'type',
        'start_date',
        'end_date',
        'renewal_date',
        'status'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function files()
    {
        return $this->hasMany(ContractFile::class, 'contract_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
