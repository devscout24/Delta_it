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
        if ($request->company_id == null) {
            return $this->success([], 'Company ID is required', 400);
        }

        $contract = Contract::where('company_id', $request->company_id)
            ->with(['files', 'associates:id,name'])
            ->first();

        if (!$contract) {
            return $this->success([], 'No contract found for this company', 200);
        }

        // file absolute URLs
        $contract->files->each(function ($file) {
            $file->file_url = asset('uploads/contracts/files/' . $file->file_path);
        });

        return $this->success($contract, 'Company contract fetched successfully', 200);
    }

    public function details($id)
    {
        $contract = Contract::where('company_id', $id)
            ->with([
                'files',
                'associates:id,name'
            ])
            ->first();

        if (!$contract) {
            return $this->success([], 'No contract found for this company', 200);
        }

        // Add full URL to files
        $contract->files->each(function ($file) {
            $file->file_url = asset('uploads/contracts/files/' . $file->file_path);
        });

        return $this->success($contract, 'Company contract fetched successfully', 200);
    }

    public function update(Request $request, $company_id)
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

        $contract = Contract::where('company_id', $company_id)->first();

        if ($contract) {
            $contract->update($request->only([
                'name',
                'type',
                'start_date',
                'end_date',
                'renewal_date',
                'status'
            ]));
        } else {
            $contract = Contract::create($request->only([
                'company_id',
                'name',
                'type',
                'start_date',
                'end_date',
                'renewal_date',
                'status'
            ]));
        }

        // ----------------------------
        // 1. SYNC ASSOCIATE COMPANIES
        // ----------------------------
        if ($request->has('associate_companies')) {
            $contract->associates()->sync($request->associate_companies);
        }

        // ----------------------------
        // 2. REMOVE FILES
        // ----------------------------
        if ($request->has('files_to_remove')) {
            $filesToDelete = ContractFile::whereIn('id', $request->files_to_remove)->get();

            foreach ($filesToDelete as $file) {
                $filePath = public_path('uploads/contracts/files/' . $file->file_path);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $file->delete();
            }
        }

        // ----------------------------
        // 3. ADD NEW FILES
        // ----------------------------
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

        return $this->success([], 'Contract updated successfully', 200);
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
