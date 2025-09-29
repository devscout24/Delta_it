<?php

namespace App\Http\Controllers\Api;


use App\Models\Contract;
use App\Traits\ApiResponse;
use App\Models\ContractFile;
use Illuminate\Http\Request;
use App\Services\ContractService;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContractRequest;

class ContractController extends Controller
{
    use ApiResponse;

    protected $contractService;
    public function __construct(ContractService $contractService)
    {
        $this->contractService = $contractService;
    }


    public function store(ContractRequest $request)
    {
        $validated  = $request->validated();

        $files = $request->file('files') ?? [];

        if (!is_array($files)) {
            $files = [$files];
        }

        $contract = $this->contractService->create($validated, $files);

        return $this->success($contract, 'Contract created successfully', 201);
    }




    public function update(ContractRequest $request)
    {
        $validated  = $request->validated();
        $this->contractService->update($request->id, $validated, $request->file('files'));
        return $this->success(null, 'Contract updated successfully');
    }


    public function destroy(Request $request)
    {

        $contract = Contract::find($request->id);


        if (!$contract) {
            return $this->error(null, 'Contract not found', 404);
        }
        $contract->delete();
        return $this->success(null, 'Contract deleted successfully', 200);
    }

    public function deleteSingleFile(Request $request)
    {
        $file = ContractFile::find($request->file_id);
        if (!$file) {
            return $this->error(null, 'File not found', 404);
        }

        $file->delete();
        return $this->success(null, 'File deleted successfully', 200);
    }
}
