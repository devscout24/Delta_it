<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule the contract expiry check to run daily at 8:00 AM
Schedule::command('contracts:check-expiry')
    ->dailyAt('08:00')
    ->description('Check for contracts expiring in 4, 7, 15, and 30 days and send reminders');
