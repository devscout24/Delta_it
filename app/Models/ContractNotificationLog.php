<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractNotificationLog extends Model
{
    protected $table = 'contract_notification_logs';

    protected $fillable = [
        'contract_id',
        'days_remaining',
        'sent_at'
    ];

    protected $dates = [
        'sent_at',
        'created_at',
        'updated_at'
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }
}
