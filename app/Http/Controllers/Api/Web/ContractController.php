<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\Contract;
use App\Models\ContractFile;
use App\Models\Company;

class ContractController extends Controller
{
    use ApiResponse;

    // ======================
    // GET CONTRACT
    // ======================
    public function show($company_id)
    {
        if (!Company::where('id', $company_id)->exists()) {
            return $this->error([], 'Company not found', 404);
        }

        $contract = Contract::with('files')
            ->where('company_id', $company_id)
            ->first();

        if (!$contract) {
            return $this->success([], 'No contract found');
        }

        $files = $contract->files->map(function ($file) {
            return [
                'id' => $file->id,
                'file_url' => Storage::url($file->file_path),
            ];
        });

        return $this->success([
            'id' => $contract->id,
            'name' => $contract->name,
            'type' => $contract->type,
            'start_date' => $contract->start_date,
            'end_date' => $contract->end_date,
            'renewal_date' => $contract->renewal_date,
            'status' => $contract->status,
            'files' => $files,
        ], 'Contract fetched');
    }

    // ======================
    // UPDATE CONTRACT
    // ======================
    public function update(Request $request, $company_id)
    {
        if (!Company::where('id', $company_id)->exists()) {
            return $this->error([], 'Company not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'renewal_date' => 'nullable|date',
            'status' => 'nullable|in:active,inactive,terminated',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $contract = Contract::where('company_id', $company_id)->first();

        if (!$contract) {
            $contract = new Contract();
            $contract->company_id = $company_id;
        }

        $contract->fill($request->only([
            'name',
            'type',
            'start_date',
            'end_date',
            'renewal_date',
            'status'
        ]));

        $contract->save();

        return $this->success([
            'contract_id' => $contract->id
        ], 'Contract saved');
    }

    // ======================
    // UPLOAD FILE
    // ======================
    public function uploadFile(Request $request)
    {
        $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'file' => 'required|file|max:4096'
        ]);

        $file = $request->file('file');
        $path = $file->store('contracts', 'public');

        $saved = ContractFile::create([
            'contract_id' => $request->contract_id,
            'file_path' => $path
        ]);

        return $this->success([
            'id' => $saved->id,
            'file_url' => Storage::url($path)
        ], 'File uploaded');
    }

    // ======================
    // DELETE FILE
    // ======================
    public function deleteFile($id)
    {
        $file = ContractFile::find($id);

        if (!$file) {
            return $this->error([], 'File not found', 404);
        }

        // delete file from storage
        if (Storage::disk('public')->exists($file->file_path)) {
            Storage::disk('public')->delete($file->file_path);
        }

        $file->delete();

        return $this->success([], 'File deleted');
    }
}
