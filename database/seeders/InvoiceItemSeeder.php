<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InvoiceItem;
use App\Models\Invoice;

class InvoiceItemSeeder extends Seeder
{
    public function run()
    {
        $invoices = Invoice::all();

        foreach ($invoices as $invoice) {

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'title' => 'Room Rent',
                'amount' => rand(300, 1000),
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'title' => 'Electricity',
                'amount' => rand(50, 300),
            ]);
        }
    }
}
