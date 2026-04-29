<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContractFile;
use App\Models\Contract;

class ContractFileSeeder extends Seeder
{
    public function run()
    {
        $contracts = Contract::all();

        foreach ($contracts as $contract) {
            ContractFile::create([
                'contract_id' => $contract->id,
                'file_path' => 'contracts/sample_contract.pdf',
            ]);
        }
    }
}
