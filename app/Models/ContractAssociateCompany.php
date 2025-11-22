<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractAssociateCompany extends Model
{
    // Fillable
    protected $fillable = [
        'contract_id',
        'company_id',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
