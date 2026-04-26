<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessCard extends Model
{
   protected $fillable = [
      'id',
      'company_id',
      "active_card",
      "lost_damage_card",
      "active_parking_card",
      "max_parking_card",
   ];


   protected $hidden = [
      'created_at',
      'updated_at'
   ];
}
