<?php

namespace App\Http\Controllers\Api;


use App\Traits\ApiResponse;
use App\Services\ContractService;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContractRequest;
use App\Models\Contract;

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


    public function destroy(string $id)
    {
        //
    }
}
