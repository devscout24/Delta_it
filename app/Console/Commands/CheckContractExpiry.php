<?php

namespace App\Console\Commands;

use App\Jobs\SendContractExpiryReminder;
use App\Models\Contract;
use App\Models\ContractNotificationLog;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckContractExpiry extends Command
{
    protected $signature = 'contracts:check-expiry';
    protected $description = 'Check for contracts expiring in 4, 7, 15, and 30 days and send reminder emails';

    public function handle()
    {
        $this->info('Checking contract expiration dates...');

        // Days to check for reminders
        $remindDays = [4, 7, 15, 30];

        foreach ($remindDays as $days) {
            $this->checkAndSendReminders($days);
        }

        $this->info('Contract expiry check completed successfully.');
    }

    protected function checkAndSendReminders($days)
    {
        // Calculate the target date
        $targetDate = Carbon::now()->addDays($days)->toDateString();

        // Find all contracts expiring on this date
        $contracts = Contract::whereDate('end_date', $targetDate)
            ->where('status', '!=', 'terminated') // Don't send for terminated contracts
            ->where('status', '!=', 'expired')
            ->get();

        foreach ($contracts as $contract) {
            // Check if we already sent a notification for this contract and days
            $alreadySent = ContractNotificationLog::where('contract_id', $contract->id)
                ->where('days_remaining', $days)
                ->where('created_at', '>=', Carbon::now()->subHours(1))
                ->exists();

            if (!$alreadySent) {
                // Dispatch the job to send the email
                SendContractExpiryReminder::dispatch($contract, $days);

                // Log the notification to prevent duplicates
                ContractNotificationLog::create([
                    'contract_id' => $contract->id,
                    'days_remaining' => $days,
                    'sent_at' => Carbon::now()
                ]);

                $this->info("Queued reminder for contract ID {$contract->id} - {$days} days remaining");
            }
        }
    }
}
