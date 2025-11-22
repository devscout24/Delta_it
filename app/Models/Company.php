<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'logo',
        'name',
        'email',
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

    public function room()
    {
        return $this->hasOne(Room::class);
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function contract()
    {
        return $this->hasOne(Contract::class);
    }
    public function contracts()
    {
        return $this->hasOne(Contract::class);
    }
}
