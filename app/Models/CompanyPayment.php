<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyPayment extends Model
{
    protected $fillable = [
        'company_id',
        'year',
        'month',
        'value_non_vat',
        'value_vat',
        'printings_non_vat',
        'printings_vat',
        'total_vat',
        'total_amount',
        'status',
    ];

    /**
     * Relationship: A company payment belongs to a company
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
