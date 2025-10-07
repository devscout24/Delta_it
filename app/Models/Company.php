<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'logo',
        'commercial_name',
        'company_email',
        'fiscal_name',
        'nif',
        'phone_number',
        'incubation_type',
        'occupied_office',
        'occupied_area',
        'bussiness_area',
        'company_manager',
        'description',
        'status'
    ];
    protected $hidden = ['created_at', 'updated_at'];
}
