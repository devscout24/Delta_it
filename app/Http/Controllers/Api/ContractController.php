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
            ->with('files')
            ->first();

        if (!$contract) {
            return $this->success([], 'No contract found for this company', 200);
        }

        // Append file URLs
        $contract->files->map(function ($file) {
            $file->file_url = $file->file_url;
        });

        return $this->success($contract, 'Company contract fetched successfully', 200);
    }

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

        $contract = Contract::where('company_id', $request->company_id)->first();

        if ($contract) {
            $contract->update($request->all());
            return $this->success($contract, 'Contract updated successfully', 200);
        }

        // Create new contract
        $contract = Contract::create($request->all());

        return $this->success($contract, 'Contract created successfully', 201);
    }

    public function storeFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file'       => 'required|file',
            'company_id' => 'required|exists:companies,id'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $contract = Contract::where('company_id', $request->company_id)->first();

        if (!$contract) {
            return $this->error([], 'Contract not created yet. Please create a contract first.', 400);
        }

        // Upload file
        $file = $request->file('file');
        $filename = time() . '_' . $file->getClientOriginalName();
        $file->move(public_path('uploads/contracts/files'), $filename);

        // Save in DB
        $saved = ContractFile::create([
            'contract_id' => $contract->id,
            'file_path'   => $filename
        ]);

        $saved->file_url = asset('uploads/contracts/files/' . $filename);

        return $this->success($saved, 'File uploaded successfully', 201);
    }

    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file_id' => 'required|exists:contract_files,id'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $file = ContractFile::find($request->file_id);
        $filePath = public_path('uploads/contracts/files/' . $file->file_path);

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $file->delete();

        return $this->success([], 'File removed successfully', 200);
    }

    public function allContracts()
    {
        $contracts = Contract::with(['company:id,name', 'files'])->get();

        $contracts->map(function ($contract) {
            $contract->files->map(function ($file) {
                $file->file_url = asset('uploads/contracts/files/' . $file->file_path);
            });
        });

        return $this->success($contracts, 'All company contracts fetched successfully', 200);
    }
}
