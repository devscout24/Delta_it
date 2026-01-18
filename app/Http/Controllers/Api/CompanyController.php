<?php

namespace App\Http\Controllers\Api;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Ticket;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CompanyController extends Controller
{
    use ApiResponse;
    public function getCompany(Request $request)
    {
        // Validate filters
        $request->validate([
            'status' => 'nullable|in:active,archived,all',
            'name'   => 'nullable|string',
            'incubation_type' => 'nullable|in:virtual,on-site,cowork,colab'
        ]);

        $status = $request->status ?? 'all';

        $query = Company::select(
            'id',
            'name',
            'email',
            'fiscal_name',
            'nif',
            'phone',
            'incubation_type',
            'business_area',
            'manager',
            'description',
            'logo',
            'status'
        );

        /** -------------------------
         *  Apply Filters Dynamically
         *  ------------------------- */

        // Filter by status
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Filter by name (LIKE %search%)
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        // Filter by incubation_type
        if ($request->filled('incubation_type')) {
            $query->where('incubation_type', $request->incubation_type);
        }

        /** -------------------------------------
         *  Fetch and process the results
         *  ------------------------------------- */
        $companies = $query->get()->map(function ($company) {
            $company->logo = $company->logo
                ? asset($company->logo)
                : asset('default/default.png');
            
            // Get end_date from contract
            $contract = Contract::where('company_id', $company->id)->first();
            $company->end_date = $contract ? $contract->end_date : null;
            
            // Get pending requests count from tickets
            $company->pending_requests = Ticket::where('company_id', $company->id)
                ->where('status', 'pending')
                ->count();
            
            return $company;
        });

        return $this->success($companies, 'Companies fetched successfully', 200);
    }
    public function addCompany(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:companies,email',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        Company::create([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        $data = Company::where('email', $request->email)->first();
        $data = [
            'id' => $data->id,
            'name' => $data->name,
            'email' => $data->email,
        ];

        return $this->success($data, 'Company Added Successful');
    }

    public function updateCompanyGeneralData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id'      => 'required|exists:companies,id',
            'logo'            => 'nullable|image|mimes:jpg,jpeg,png,svg|max:2048',
            'name'            => 'nullable|string|max:255',
            'fiscal_name'     => 'nullable|string|max:255',
            'email'           => 'nullable|email|unique:companies,email,' . $request->company_id,
            'nif'             => 'nullable|string|max:50',
            'phone'           => 'nullable|string|max:20',
            'incubation_type' => 'required|in:virtual,on-site,cowork,colab',
            'business_area'   => 'nullable|array',
            'manager'         => 'nullable|string|max:100',
            'description'     => 'nullable|string',
            'status'          => 'nullable|in:active,archived',
            'rooms' => 'nullable|array',
            'rooms.*' => 'integer|exists:rooms,id',

        ], [
            'name.required'            => 'The company name is required.',
            'email.unique'             => 'This email is already registered.',
            'incubation_type.required' => 'Please select an incubation type.',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        $company = Company::find($request->company_id);

        if (!$company) {
            return $this->error('', 'Company not found', 404);
        }

        // Upload logo if provided
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');

            $uploadPath = $this->uploadImage(
                $file,
                $company->logo,
                'uploads/companyLogo',
                150,
                150
            );
        } else {
            $uploadPath = $company->logo;
        }

        try {
            $company->update([
                'name'            => $request->name ?? $company->name,
                'email'           => $request->email ?? $company->email,
                'fiscal_name'     => $request->fiscal_name,
                'nif'             => $request->nif,
                'phone'           => $request->phone,
                'incubation_type' => $request->incubation_type,
                'business_area'   => json_encode($request->business_area),
                'manager'         => $request->manager,
                'description'     => $request->description,
                'logo'            => $uploadPath,
                'status'          => $request->status ?? $company->status,
            ]);

            if ($request->has('rooms')) {

                $newRoomIds = $request->rooms; // e.g. [1,2]

                // 1. Remove rooms currently assigned to the company but not in new list
                Room::where('company_id', $company->id)
                    ->whereNotIn('id', $newRoomIds)
                    ->update(['company_id' => null]);

                // 2. Assign new rooms to the company
                Room::whereIn('id', $newRoomIds)
                    ->update(['company_id' => $company->id]);
            }

            return $this->success((object)[], 'Company General Data updated successfully', 200);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 'Something went wrong', 500);
        }
    }

    public function uploadLogo(Request $request)
    {

        // Validate file input
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,svg|max:2048',
            'company_id' => 'required|exists:companies,id',
        ]);

        $company = Company::find($request->company_id);

        // Handle file upload
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');


            if ($company->logo && file_exists(public_path($company->logo))) {
                unlink(public_path($company->logo));
            }

            // Generate unique file name
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // Save file to public/uploads/companyLogo
            $uploadPath = 'uploads/companyLogo/';
            $file->move(public_path($uploadPath), $filename);

            // Update company record
            $company->logo = $uploadPath . $filename;
            $company->save();

            return $this->success(asset($company->logo), 'Logo uploaded successfully', 200);
        }

        return $this->error(null, 'Comapany Logo not fond', 201);
    }
    public function deleteLogo(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $company = Company::find($request->company_id);

        if (!$company) {
            return $this->error(null, 'Company not found', 404);
        }

        if ($company->logo && file_exists(public_path($company->logo))) {
            unlink(public_path($company->logo));
            $company->logo = null;
            $company->save();

            return $this->success(null, 'Company Logo deleted successfully', 200);
        }

        return $this->error(null, 'Company Logo not found', 201);
    }
    public function deleteCompany(Request $request)
    {
        if (!$request->id) {
            return $this->error('', 'Id not sent', 404);
        }

        $company = Company::where('id', $request->id)->first();

        if (!$company) {
            return $this->error('', 'No company found', 404);
        }

        $company->delete();

        return $this->success((object)[], 'Company deleted successfully', 200);
    }
    public function show($id)
    {
        $company = Company::select(
            'id',
            'name',
            'email',
            'fiscal_name',
            'nif',
            'phone',
            'incubation_type',
            'business_area',
            'manager',
            'description',
            'logo',
            'status'
        )
            ->with(['rooms:id,floor,room_name,area,company_id']) // load rooms
            ->find($id);

        if (!$company) {
            return $this->error(null, 'Company not found', 404);
        }

        // Format logo
        $company->logo = $company->logo
            ? asset($company->logo)
            : asset('default/default.png');

        // If company has rooms, calculate total area
        $rooms = $company->rooms ?? collect([]);

        $company->occupied_rooms = $rooms->map(function ($room) {
            return [
                'id'         => $room->id,
                'room_name'  => $room->room_name,
                'area'       => $room->area,
            ];
        });

        $company->total_area_occupied = $rooms->sum('area');

        // (Optional) remove original rooms key
        unset($company->rooms);

        return $this->success($company, 'Company fetched successfully', 200);
    }

    public function getSpecificCompanies(Request $request)
    {
        if (!$request->id) {
            return $this->error('', 'Id not sent', 404);
        }

        $company = Company::where('id', $request->id)->first();

        if (!$company) {
            return $this->error('', 'No company found', 404);
        }

        $company = [
            'id' => $company->id,
            'commercial_name' => $company->commercial_name,
            'company_email' => $company->company_email,
        ];
        return $this->success($company, 'Company fetched successful', 200);
    }




    // For Mobile Api

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'            => 'nullable|string|max:255',
            'email'           => 'nullable|email|max:255',
            'fiscal_name'     => 'nullable|string|max:255',
            'nif'             => 'nullable|string|max:50',
            'phone'           => 'nullable|string|max:20',
            'incubation_type' => 'nullable|in:virtual,on-site,cowork,colab',
            'business_area'   => 'nullable|string|max:255',
            'manager'         => 'nullable|string|max:100',
            'description'     => 'nullable|string',
            'logo'            => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        // Get logged-in user and their company
        $user = Auth::guard('api')->user();
        $company = Company::find($user->company_id);

        if (!$company) {
            return $this->error('', 'Company not found', 404);
        }

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $uploadPath = $this->uploadImage($file, $company->logo, 'uploads/companyLogo', 150, 150);
        } else {
            $uploadPath = $company->logo;
        }

        // Update company data
        $company->update([
            'name'            => $request->name,
            'email'           => $request->email,
            'fiscal_name'     => $request->fiscal_name,
            'nif'             => $request->nif,
            'phone'           => $request->phone,
            'incubation_type' => $request->incubation_type,
            'business_area'   => $request->business_area,
            'manager'         => $request->manager,
            'description'     => $request->description,
            'logo'            => $uploadPath,
        ]);

        return $this->success([], 'Company information updated successfully', 200);
    }

    public function archiveCompany(Request $request)
    {
        $company = Company::find($request->id);
        $company->status = 'archived';
        $company->save();
        return $this->success([], 'Company archived successfully', 200);
    }
    public function restoreCompany(Request $request)
    {
        $company = Company::find($request->id);
        $company->status = 'active';
        $company->save();
        return $this->success([], 'Company restored successfully', 200);
    }
}
