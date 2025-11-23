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
    public function index()
    {
        $contracts = Contract::whereNull('company_id')
            ->with(['files', 'associates:id,name'])
            ->get();

        $contracts->each(function ($contract) {
            $contract->files->each(function ($file) {
                $file->file_url = asset('uploads/contracts/files/' . $file->file_path);
            });
        });

        return $this->success($contracts, 'Internal contracts fetched successfully', 200);
    }
    public function show($id)
    {
        $contract = Contract::whereNull('company_id')
            ->where('id', $id)
            ->with(['files', 'associates:id,name'])
            ->first();

        if (!$contract) {
            return $this->error([], 'Contract not found', 404);
        }

        $contract->files->each(function ($file) {
            $file->file_url = asset('uploads/contracts/files/' . $file->file_path);
        });

        return $this->success($contract, 'Contract details fetched successfully', 200);
    }
    public function store(Request $request)
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

            'files' => 'nullable|array',
            'files.*' => 'file',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $contract = Contract::create([
            'company_id' => null,
            'name' => $request->name,
            'type' => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'renewal_date' => $request->renewal_date,
            'status' => $request->status,
        ]);

        if ($request->has('associate_companies')) {
            $contract->associates()->sync($request->associate_companies);
        }

        // Upload files
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $uploadedFile) {
                $filename = uniqid() . '_' . $uploadedFile->getClientOriginalName();
                $uploadedFile->move(public_path('uploads/contracts/files'), $filename);

                ContractFile::create([
                    'contract_id' => $contract->id,
                    'file_path' => $filename
                ]);
            }
        }

        return $this->success($contract, 'Internal contract created successfully', 201);
    }
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'type' => 'nullable|in:full-time,part-time,contractor',
            'start_date' => 'nullable|date',
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

        $contract = Contract::whereNull('company_id')->where('id', $id)->first();

        if (!$contract) {
            return $this->error([], 'Contract not found', 404);
        }

        $contract->update($request->only([
            'name',
            'type',
            'start_date',
            'end_date',
            'renewal_date',
            'status'
        ]));

        if ($request->has('associate_companies')) {
            $contract->associates()->sync($request->associate_companies);
        }

        // Delete old files
        if ($request->has('files_to_remove')) {
            $files = ContractFile::whereIn('id', $request->files_to_remove)->get();
            foreach ($files as $file) {
                $path = public_path('uploads/contracts/files/' . $file->file_path);
                if (file_exists($path)) unlink($path);
                $file->delete();
            }
        }

        // Add new files
        if ($request->hasFile('files_to_add')) {
            foreach ($request->file('files_to_add') as $uploadedFile) {
                $filename = uniqid() . '_' . $uploadedFile->getClientOriginalName();
                $uploadedFile->move(public_path('uploads/contracts/files'), $filename);

                ContractFile::create([
                    'contract_id' => $contract->id,
                    'file_path' => $filename
                ]);
            }
        }

        return $this->success([], 'Contract updated successfully', 200);
    }
    public function destroy($id)
    {
        $contract = Contract::whereNull('company_id')
            ->where('id', $id)
            ->with('files')
            ->first();

        if (!$contract) {
            return $this->error([], 'Contract not found', 404);
        }

        // Delete files
        foreach ($contract->files as $file) {
            $path = public_path('uploads/contracts/files/' . $file->file_path);
            if (file_exists($path)) unlink($path);
            $file->delete();
        }

        // detach associates
        $contract->associates()->detach();

        $contract->delete();

        return $this->success([], 'Contract deleted successfully', 200);
    }
}
