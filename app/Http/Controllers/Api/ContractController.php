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




    public function update(Request $request)
    {
        $validated  = app(ContractRequest::class)->validated();
        $files = $request->file('files') ?? [];
        if (!is_array($files)) {
            $files = [$files];
        }
        $this->contractService->update($request->id, $validated, $files);
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

        $file = ContractFile::where('id', $request->id)->where('contract_id', $request->contract_id)->first();

        if (!$file) {
            return $this->error(null, 'File not found', 404);
        }

        $file->delete();
        return $this->success(null, 'File deleted successfully', 200);
    }


    public function show()
    {
        $contract = Contract::with('company')->first();

        if (!$contract) {
            return $this->error(null, 'Contract not found', 404);
        }

        $data = [
            'name'         => $contract->name,
            'type'         => $contract->type,
            'start_date'   => $contract->start_date,
            'end_date'     => $contract->end_date,
            'renewal_date' => $contract->renewal_date,
            'status'       => $contract->status,
            'company'      => $contract->company ? [
                'commercial_name' => $contract->company->commercial_name,
                'logo'            => $contract->company->logo ? asset($contract->company->logo) : null,
            ] : null,
        ];

        return $this->success($data, 'Contract details', 200);
    }
}
