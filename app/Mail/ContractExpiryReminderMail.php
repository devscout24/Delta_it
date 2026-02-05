<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContractExpiryReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $company;
    public $contract;
    public $daysRemaining;

    public function __construct($company, $contract, $daysRemaining)
    {
        $this->company = $company;
        $this->contract = $contract;
        $this->daysRemaining = $daysRemaining;
    }

    public function build()
    {
        return $this->subject('Contract Expiry Reminder: ' . $this->daysRemaining . ' days remaining')
            ->view('emails.contract_expiry_reminder', [
                'company' => $this->company,
                'contract' => $this->contract,
                'daysRemaining' => $this->daysRemaining
            ]);
    }
}
