<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $appends = [
        'room_name',
        'floor_no',
    ];

    protected $fillable = [
        'floor_id',
        'name',
        'area',
        'polygon_points',
        'status',
    ];

    protected $casts = [
        'polygon_points' => 'array',
    ];

    public function floor()
    {
        return $this->belongsTo(Floor::class);
    }

    public function getRoomNameAttribute(): ?string
    {
        return $this->name;
    }

    public function getFloorNoAttribute(): ?int
    {
        return $this->floor?->level;
    }

    public function allocations()
    {
        return $this->hasMany(RoomAllocation::class);
    }

    public function activeAllocation()
    {
        return $this->hasOne(RoomAllocation::class)
            ->where('status', 'active');
    }

    public function currentAllocation()
    {
        return $this->activeAllocation();
    }
}
