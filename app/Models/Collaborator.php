<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collaborator extends Model
{
    protected $fillable = [
        'company_id',
        'first_name',
        'last_name',
        'job_position',
        'email',
        'phone_number',
        'phone_extension',
        'access_card_number',
        'parking_card',
    ];

    // ======================
    // RELATION
    // ======================
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
