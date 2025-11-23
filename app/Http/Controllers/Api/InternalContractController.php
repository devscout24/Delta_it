<?php

namespace App\Http\Controllers\API;

use App\Models\Contract;
use App\Traits\ApiResponse;
use App\Models\ContractFile;
use Illuminate\Http\Request;
use App\Services\ContractService;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContractRequest;
use Illuminate\Support\Facades\Validator;

class InternalContractController extends Controller
{
    use ApiResponse;

    // ============================
    // 1. INTERNAL CONTRACT LIST
    // ============================
    public function index()
    {
        $contract = Contract::whereNull('company_id')
            ->with(['files', 'associates:id,name'])
            ->first();

        if (!$contract) {
            return $this->success([], 'No internal contract found', 200);
        }

        $contract->files->each(function ($file) {
            $file->file_url = asset('uploads/contracts/files/' . $file->file_path);
        });

        return $this->success($contract, 'Internal contract fetched successfully', 200);
    }

    // ============================
    // 2. INTERNAL CONTRACT DETAILS
    // ============================
    public function details()
    {
        $contract = Contract::whereNull('company_id')
            ->with(['files', 'associates:id,name'])
            ->first();

        if (!$contract) {
            return $this->success([], 'No internal contract found', 200);
        }

        $contract->files->each(function ($file) {
            $file->file_url = asset('uploads/contracts/files/' . $file->file_path);
        });

        return $this->success($contract, 'Internal contract fetched successfully', 200);
    }

    // ============================
    // 3. INTERNAL CONTRACT UPDATE
    // ============================
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:full-time,part-time,contractor',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'renewal_date' => 'nullable|date',
            'status' => 'nullable|in:active,inactive,terminated,expired',

            'associate_companies' => 'nullable|array',
            'associate_companies.*' => 'integer|exists:companies,id',

            'files_to_add' => 'nullable|array',
            'files_to_add.*' => 'file',

            'files_to_remove' => 'nullable|array',
            'files_to_remove.*' => 'integer|exists:contract_files,id',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        // Fetch internal contract
        $contract = Contract::whereNull('company_id')->first();

        // If no internal contract exists → create it
        if (!$contract) {
            $contract = Contract::create([
                'company_id' => null,
                'name' => $request->name,
                'type' => $request->type,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'renewal_date' => $request->renewal_date,
                'status' => $request->status
            ]);
        } else {
            // Update existing internal contract
            $contract->update($request->only([
                'name',
                'type',
                'start_date',
                'end_date',
                'renewal_date',
                'status'
            ]));
        }

        // ---------------------------
        // 1. Sync associate companies
        // ---------------------------
        if ($request->has('associate_companies')) {
            $contract->associates()->sync($request->associate_companies);
        }

        // ---------------------------
        // 2. Remove old files
        // ---------------------------
        if ($request->has('files_to_remove')) {
            $files = ContractFile::whereIn('id', $request->files_to_remove)->get();

            foreach ($files as $file) {
                $path = public_path('uploads/contracts/files/' . $file->file_path);
                if (file_exists($path)) {
                    unlink($path);
                }
                $file->delete();
            }
        }

        // ---------------------------
        // 3. Add new files
        // ---------------------------
        if ($request->hasFile('files_to_add')) {
            foreach ($request->file('files_to_add') as $uploadedFile) {
                $filename = time() . '_' . $uploadedFile->getClientOriginalName();
                $uploadedFile->move(public_path('uploads/contracts/files'), $filename);

                ContractFile::create([
                    'contract_id' => $contract->id,
                    'file_path' => $filename
                ]);
            }
        }

        return $this->success([], 'Internal contract updated successfully', 200);
    }
}
