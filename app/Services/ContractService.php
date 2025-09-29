<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractFile;

class ContractService extends Controller
{


    public function create(array $data, $files = null)
    {
        // Create contract record
        $contract = Contract::create([
            'name' => $data['name'],
            'type' => $data['type'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'renewal_date' => $data['renewal_date'] ?? null,
            'status' => $data['status'] ?? 'active',
            'company_id' => $data['company_id'],
        ]);

        if ($files) {
            foreach ($files as $file) {
                $filePath = $this->uploadFile($file, 'contracts');
                ContractFile::create([
                    'contract_id' => $contract->id,
                    'file_path' => $filePath,
                ]);
            }
        }

        return $contract;
    }


    public function update($id, array $data, $files = null)
    {
        $contract = Contract::findOrFail($id);

        $contract->update([
            'name' => $data['name'],
            'type' => $data['type'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'renewal_date' => $data['renewal_date'] ?? null,
            'status' => $data['status'] ?? $contract->status,
            'company_id' => $data['company_id'],
        ]);

        if ($files) {
            foreach ($files as $file) {
                $filePath = $this->uploadFile($file, 'contracts');
                ContractFile::create([
                    'contract_id' => $contract->id,
                    'file_path' => $filePath,
                ]);
            }
        }

        return;
    }
}
