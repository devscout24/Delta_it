<?php

namespace App\Http\Controllers\Api;


use App\Models\Contract;
use App\Traits\ApiResponse;
use App\Models\ContractFile;
use Illuminate\Http\Request;
use App\Services\ContractService;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContractRequest;
use Illuminate\Support\Facades\Validator;

class ContractController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $contract = Contract::where('company_id', $request->company_id)
            ->with(['files'])
            ->first();

        if (!$contract) {
            return $this->success([], 'No contract found for this company', 200);
        }

        // Add full URL for files
        $contract->files->map(function ($file) {
            $file->file_url = asset($file->file_path);
        });

        return $this->success($contract, 'Company contract fetched successfully', 200);
    }

    /**
     * CREATE OR UPDATE CONTRACT
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id'    => 'required|exists:companies,id',
            'name'          => 'required|string|max:255',
            'type'          => 'required|in:full-time,part-time,contractor',
            'start_date'    => 'required|date',
            'end_date'      => 'nullable|date',
            'renewal_date'  => 'nullable|date',
            'status'        => 'nullable|in:active,inactive,terminated,expired',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        // Check if contract exists
        $contract = Contract::where('company_id', $request->company_id)->first();

        if ($contract) {
            // Update existing contract
            $contract->update($request->only([
                'name',
                'type',
                'start_date',
                'end_date',
                'renewal_date',
                'status'
            ]));

            return $this->success($contract, 'Contract updated successfully', 200);
        }

        // Create new contract
        $contract = Contract::create([
            'company_id'   => $user->company_id,
            'name'         => $request->name,
            'type'         => $request->type,
            'start_date'   => $request->start_date,
            'end_date'     => $request->end_date,
            'renewal_date' => $request->renewal_date,
            'status'       => $request->status ?? 'active',
        ]);

        return $this->success($contract, 'Contract created successfully', 201);
    }

    /**
     * ADD CONTRACT FILE
     */
    public function storeFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file'       => 'required|file',
            'company_id' => 'required|exists:companies,id'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        // Fetch company's contract
        $contract = Contract::where('company_id', $request->company_id)->first();

        if (!$contract) {
            return $this->error([], 'Contract not created yet. Please create contract first.', 400);
        }

        // Upload file
        $path = $request->file('file')->store('contract_files', 'public');

        // Save in DB
        $file = ContractFile::create([
            'contract_id' => $contract->id,
            'file_path'   => $path,
        ]);

        $file->file_url = asset($file->file_path);

        return $this->success($file, 'File uploaded successfully', 201);
    }

    /**
     * DELETE CONTRACT FILE
     */
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file_id' => 'required|exists:contract_files,id'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $file = ContractFile::find($request->file_id);

        if (!$file) {
            return $this->error([], 'File not found', 404);
        }

        // delete file from storage
        if (file_exists(public_path($file->file_path))) {
            unlink(public_path($file->file_path));
        }

        $file->delete();

        return $this->success([], 'File removed successfully', 200);
    }

    /**
     * GET ALL COMPANY CONTRACTS + FILES
     */
    public function allContracts()
    {
        $contracts = Contract::with(['company:id,name', 'files'])->get();

        // Add file URLs
        $contracts->map(function ($contract) {
            $contract->files->map(function ($file) {
                $file->file_url = asset($file->file_path);
            });
        });

        return $this->success($contracts, 'All company contracts fetched successfully', 200);
    }
}
