<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'room_name',
        'area',
        'polygon_points',
        'company_id',
        'status',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];


    /**
     * Get the company that occupies this room.
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
