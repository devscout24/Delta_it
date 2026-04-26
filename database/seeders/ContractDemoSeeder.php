<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Contract;
use App\Models\ContractFile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ContractDemoSeeder extends Seeder
{
    /**
     * Seed demo contract data used by mobile contracts screen.
     */
    public function run(): void
    {
        $companyId = User::query()
            ->whereNotNull('company_id')
            ->value('company_id');

        if (! $companyId) {
            $company = Company::firstOrCreate(
                ['email' => 'demo.contracts@technova.com'],
                [
                    'name' => 'TechNova Demo Company',
                    'status' => 'active',
                ]
            );

            $companyId = $company->id;
        }

        $contract = Contract::updateOrCreate(
            ['company_id' => $companyId],
            [
                'name' => 'Demo Service Agreement',
                'type' => 'annual',
                'start_date' => now()->subMonths(3)->toDateString(),
                'end_date' => now()->addMonths(9)->toDateString(),
                'renewal_date' => now()->addMonths(10)->toDateString(),
                'status' => 'active',
            ]
        );

        $directory = public_path('uploads/contracts/files');
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0775, true);
        }

        $demoFiles = [
            'demo_contract_overview.pdf' => "Demo contract overview for frontend testing.\nGenerated at: " . now()->toDateTimeString(),
            'demo_contract_terms.pdf' => "Demo terms and conditions for frontend testing.\nGenerated at: " . now()->toDateTimeString(),
        ];

        foreach ($demoFiles as $filename => $content) {
            $fullPath = $directory . DIRECTORY_SEPARATOR . $filename;

            if (! File::exists($fullPath)) {
                File::put($fullPath, $content);
            }

            ContractFile::firstOrCreate([
                'contract_id' => $contract->id,
                'file_path' => $filename,
            ]);
        }
    }
}
