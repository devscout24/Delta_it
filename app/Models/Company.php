<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'nif',
        'incubation_type',
        'business_area',
        'manager_name',
        'description',
        'logo',
        'status',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function roomAllocations()
    {
        return $this->hasMany(RoomAllocation::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }
}
