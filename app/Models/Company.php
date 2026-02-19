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
        'phone',
        'incubation_type',
        'business_area',
        'manager',
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
