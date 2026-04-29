<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                ->orWhereHas('company', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%');
                });
        }

        $contracts = $query->latest()->paginate(10);

        $data = $contracts->getCollection()->map(function ($c) {
            return [
                'id' => $c->id,
                'name' => $c->name,
                'type' => $c->type,

                'company' => $c->company?->name,

                'start_date' => $c->start_date,
                'end_date' => $c->end_date,
                'status' => $c->status,
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

        $contract = Contract::create($request->only([
            'company_id',
            'name',
            'type',
            'start_date',
            'end_date',
            'renewal_date',
            'status'
        ]));

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
            'files' => $contract->files
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

        $contract->update($request->only([
            'company_id',
            'name',
            'type',
            'start_date',
            'end_date',
            'renewal_date',
            'status'
        ]));

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
        $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'file' => 'required|file|max:4096'
        ]);

        $file = $request->file('file');
        $name = time() . '_' . $file->getClientOriginalName();

        $file->move(public_path('uploads/contracts'), $name);

        $saved = ContractFile::create([
            'contract_id' => $request->contract_id,
            'file_path' => $name
        ]);

        return $this->success([
            'id' => $saved->id,
            'file_url' => asset('uploads/contracts/' . $name)
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

        $path = public_path('uploads/contracts/' . $file->file_path);

        if (file_exists($path)) {
            unlink($path);
        }

        $file->delete();

        return $this->success([], 'Deleted');
    }
}
