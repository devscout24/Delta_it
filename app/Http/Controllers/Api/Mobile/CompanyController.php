<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Company;
use App\Models\RoomAllocation;
use App\Models\Contract;

class CompanyController extends Controller
{
    use ApiResponse;

    // ======================
    // GET COMPANY INFO
    // ======================
    public function info()
    {
        $user = Auth::guard('api')->user();

        if (!$user || !$user->company_id) {
            return $this->error([], 'Unauthorized', 401);
        }

        $company = Company::find($user->company_id);

        if (!$company) {
            return $this->error([], 'Company not found', 404);
        }

        // Get occupied rooms via allocations
        $allocations = RoomAllocation::with('room')
            ->where('company_id', $company->id)
            ->where('status', 'active')
            ->get();

        $rooms = $allocations->map(function ($item) {
            return [
                'id' => $item->room->id,
                'name' => $item->room->name,
                'floor_id' => $item->room->floor_id,
                'area' => (float) $item->room->area,
            ];
        });

        // Get contract
        $contract = Contract::where('company_id', $company->id)->first();

        return $this->success([
            'id' => $company->id,
            'name' => $company->name,
            'email' => $company->email,
            'phone' => $company->phone,
            'nif' => $company->nif,
            'incubation_type' => $company->incubation_type,
            'business_area' => $company->business_area,
            'manager_name' => $company->manager_name,
            'description' => $company->description,
            'status' => $company->status,
            'logo' => $company->logo ? asset($company->logo) : null,
            'occupied_rooms' => $rooms,
            'occupied_area' => $rooms->sum('area'),

            'contract' => [
                'start_date' => $contract?->start_date,
                'end_date' => $contract?->end_date,
            ],
        ], 'Company info fetched');
    }

    // ======================
    // UPDATE COMPANY
    // ======================
    public function update(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (!$user || !$user->company_id) {
            return $this->error([], 'Unauthorized', 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'nif' => 'nullable|string|max:50',
            'incubation_type' => 'nullable|in:virtual,on-site,cowork,colab',
            'business_area' => 'nullable|string',
            'manager_name' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $company = Company::find($user->company_id);

        if (!$company) {
            return $this->error([], 'Company not found', 404);
        }

        $data = $request->only([
            'name',
            'email',
            'phone',
            'nif',
            'incubation_type',
            'business_area',
            'manager_name',
            'description',
        ]);

        // ✅ USE YOUR HELPER HERE
        if ($request->hasFile('logo')) {
            $data['logo'] = $this->uploadImage(
                $request->file('logo'),
                $company->logo,              // old image
                'uploads/company',           // folder
                200,                         // width
                200,                         // height
                'company-logo'               // custom name
            );
        }

        $company->update($data);

        return $this->success([
            'logo_url' => $company->logo ? asset($company->logo) : null
        ], 'Company updated successfully');
    }
}
