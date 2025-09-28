<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractFile extends Model
{
    protected $fillable = [
        'contract_id',
        'file_path',
    ];
}
