<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\Contract;

class ContractController extends Controller
{
    use ApiResponse;

    // ======================
    // GET COMPANY CONTRACT
    // ======================
    public function index()
    {
        $user = Auth::guard('api')->user();

        if (!$user || !$user->company_id) {
            return $this->error([], 'Unauthorized', 401);
        }

        $contract = Contract::where('company_id', $user->company_id)
            ->with('files')
            ->first();

        if (!$contract) {
            return $this->success([
                'has_contract' => false,
                'message' => 'No contract found'
            ], 'No contract');
        }

        // Format files
        $files = $contract->files->map(function ($file) {
            return [
                'id' => $file->id,
                'file_name' => basename($file->file_path),
                'file_url' => asset('uploads/contracts/files/' . $file->file_path),
            ];
        });

        return $this->success([
            'has_contract' => true,

            'contract' => [
                'id' => $contract->id,
                'name' => $contract->name,
                'type' => $contract->type,
                'start_date' => $contract->start_date,
                'end_date' => $contract->end_date,
                'renewal_date' => $contract->renewal_date,
                'status' => $contract->status,
            ],

            'files' => $files

        ], 'Contract fetched successfully');
    }
}
