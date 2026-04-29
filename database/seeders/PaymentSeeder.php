<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Payment;
use App\Models\Invoice;

class PaymentSeeder extends Seeder
{
    public function run()
    {
        $paidInvoices = Invoice::where('status', 'paid')->get();

        foreach ($paidInvoices as $invoice) {

            Payment::create([
                'invoice_id' => $invoice->id,
                'amount' => $invoice->total_amount,
                'method' => 'cash',
                'payment_date' => now(),
            ]);
        }
    }
}
