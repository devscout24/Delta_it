<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\Contract;
use App\Models\ContractFile;

class AdminContractController extends Controller
{
    use ApiResponse;

    // ======================
    // LIST ALL CONTRACTS
    // ======================
    public function index(Request $request)
    {
        $query = Contract::with('company');

        // Search (contract + company)
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhereHas('company', function ($q2) use ($request) {
                        $q2->where('name', 'like', '%' . $request->search . '%');
                    });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Date filters
        if ($request->filled('start_date')) {
            $query->whereDate('start_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('end_date', '<=', $request->end_date);
        }

        $contracts = $query->latest()->paginate(10);

        $data = $contracts->getCollection()->map(function ($c) {
            return [
                'id' => $c->id,
                'name' => $c->name,
                'company' => $c->company?->name,

                'start_date' => optional($c->start_date)->format('d/m/Y'),
                'end_date' => optional($c->end_date)->format('d/m/Y'),

                'status' => ucfirst($c->status),
            ];
        });

        return $this->success([
            'data' => $data,
            'pagination' => [
                'current_page' => $contracts->currentPage(),
                'last_page' => $contracts->lastPage(),
                'total' => $contracts->total(),
            ]
        ], 'Contracts fetched');
    }

    // ======================
    // STORE
    // ======================
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string',
            'type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'renewal_date' => 'nullable|date',
            'status' => 'required|in:active,terminated',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $contract = Contract::create($request->all());

        return $this->success($contract, 'Contract created', 201);
    }

    // ======================
    // SHOW
    // ======================
    public function show($id)
    {
        $contract = Contract::with('files')->find($id);

        if (!$contract) {
            return $this->error([], 'Not found', 404);
        }

        return $this->success([
            'id' => $contract->id,
            'company_id' => $contract->company_id,
            'name' => $contract->name,
            'type' => $contract->type,
            'start_date' => $contract->start_date,
            'end_date' => $contract->end_date,
            'renewal_date' => $contract->renewal_date,
            'status' => $contract->status,
            'files' => $contract->files->map(fn($f) => [
                'id' => $f->id,
                'file_url' => Storage::url($f->file_path),
            ])
        ], 'Contract details');
    }

    // ======================
    // UPDATE
    // ======================
    public function update(Request $request, $id)
    {
        $contract = Contract::find($id);

        if (!$contract) {
            return $this->error([], 'Not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string',
            'type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'renewal_date' => 'nullable|date',
            'status' => 'required|in:active,terminated',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $contract->update($request->all());

        return $this->success($contract, 'Updated');
    }

    // ======================
    // DELETE
    // ======================
    public function destroy($id)
    {
        $contract = Contract::find($id);

        if (!$contract) {
            return $this->error([], 'Not found', 404);
        }

        $contract->delete();

        return $this->success([], 'Deleted');
    }

    // ======================
    // UPLOAD FILE
    // ======================
    public function uploadFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contract_id' => 'required|exists:contracts,id',
            'file' => 'required|file|max:4096'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $path = $request->file('file')->store('contracts', 'public');

        $saved = ContractFile::create([
            'contract_id' => $request->contract_id,
            'file_path' => $path
        ]);

        return $this->success([
            'id' => $saved->id,
            'file_url' => Storage::url($path)
        ], 'Uploaded');
    }

    // ======================
    // DELETE FILE
    // ======================
    public function deleteFile($id)
    {
        $file = ContractFile::find($id);

        if (!$file) {
            return $this->error([], 'Not found', 404);
        }

        if (Storage::disk('public')->exists($file->file_path)) {
            Storage::disk('public')->delete($file->file_path);
        }

        $file->delete();

        return $this->success([], 'Deleted');
    }
}
