<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Invoice;
use App\Models\Company;

class InvoiceSeeder extends Seeder
{
    public function run()
    {
        $companies = Company::where('status', 'active')->get();

        foreach ($companies as $company) {

            for ($i = 1; $i <= 3; $i++) {

                Invoice::create([
                    'company_id' => $company->id,
                    'invoice_date' => now()->subMonths($i),
                    'due_date' => now()->subMonths($i)->addDays(10),
                    'total_amount' => rand(500, 2000),
                    'vat_amount' => rand(50, 200),
                    'status' => rand(0, 1) ? 'paid' : 'pending',
                ]);
            }
        }
    }
}
