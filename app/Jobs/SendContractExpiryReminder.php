<?php

namespace App\Jobs;

use App\Mail\ContractExpiryReminderMail;
use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendContractExpiryReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $contract;
    protected $daysRemaining;

    public function __construct(Contract $contract, $daysRemaining)
    {
        $this->contract = $contract;
        $this->daysRemaining = $daysRemaining;
    }

    public function handle()
    {
        // Get the company associated with the contract
        $company = $this->contract->company;

        if (!$company || !$company->email) {
            return;
        }

        // Send the email
        Mail::to($company->email)->send(new ContractExpiryReminderMail($company, $this->contract, $this->daysRemaining));
    }
}
