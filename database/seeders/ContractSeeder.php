<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Contract;
use App\Models\Company;

class ContractSeeder extends Seeder
{
    public function run()
    {
        $companies = Company::where('status', 'active')->get();

        foreach ($companies as $company) {
            Contract::create([
                'company_id' => $company->id,
                'name' => $company->name . ' Agreement',
                'type' => 'annual',
                'start_date' => now()->subYear(),
                'end_date' => now()->addYear(),
                'renewal_date' => now()->addYear()->addMonth(),
                'status' => 'active',
            ]);
        }
    }
}
