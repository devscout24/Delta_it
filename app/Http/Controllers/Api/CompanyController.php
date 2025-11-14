<?php

namespace App\Http\Controllers\Api;

use App\Models\Company;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
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
            'logo'            => 'nullable|image|mimes:jpg,jpeg,png,svg|max:2048',
            'name'            => 'required|string|max:255',
            'fiscal_name'     => 'nullable|string|max:255',
            'email'           => 'required|email|unique:companies,email,' . $request->company_id,
            'nif'             => 'nullable|integer',
            'phone_number'    => 'nullable|string|max:11',
            'incubation_type' => 'required|in:virtual,on-site,cowork,colab',
            'occupied_office' => 'nullable|string',
            'occupied_area'   => 'nullable|string|max:11',
            'bussiness_area'  => 'nullable|string',
            'company_manager' => 'nullable|string|max:100',
            'description'     => 'nullable|string|max:255',
        ], [
            'logo.image'               => 'The logo must be an image file.',
            'logo.mimes'               => 'The logo must be a JPG, JPEG, PNG, or SVG file.',
            'logo.max'                 => 'The logo must not be larger than 2MB.',
            'name.required' => 'The commercial name is required.',
            'name.max'      => 'The commercial name cannot exceed 255 characters.',
            'fiscal_name.max'          => 'The fiscal name cannot exceed 255 characters.',
            'email.required'   => 'The company email is required.',
            'email.email'      => 'Please enter a valid email address.',
            'email.unique'     => 'This email is already registered.',
            'nif.integer'              => 'The NIF must be a valid number.',
            'phone_number.max'         => 'The phone number cannot exceed 11 digits.',
            'incubation_type.required' => 'Please select an incubation type.',
            'incubation_type.in'       => 'The selected incubation type is invalid.',
            'occupied_office.string'   => 'The occupied office field must be text.',
            'occupied_area.max'        => 'The occupied area cannot exceed 11 characters.',
            'company_manager.max'      => 'The company manager name cannot exceed 100 characters.',
            'description.max'          => 'The description cannot exceed 255 characters.',
            'status.required'          => 'The company status is required.',
            'status.in'                => 'The selected status is invalid.',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 200);
        }

        if (!$request->id) {
            return $this->error('', 'Id not sent', 200);
        }

        $occpaied_office = json_encode($request->occupied_office);
        $bussiness_area = json_encode($request->bussiness_area);

        if ($request->id) {
            $company = Company::where('id', $request->id)->first();
        }

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $uploadPath =  $this->uploadImage($file, $company->logo, 'uploads/companyLogo', 150, 150);
        } else {
            $uploadPath = $company->logo ?? null;
        }

        try {
            Company::Where('id', $request->id)->update([
                'name'            => $request->commercial_name,
                'email'           => $request->company_email,
                'fiscal_name'     => $request->fiscal_name,
                'nif'             => $request->nif,
                'phone_number'    => $request->phone_number,
                'incubation_type' => $request->incubation_type,
                'occupied_office' => $occpaied_office,
                'occupied_area'   => $request->occupied_area,
                'bussiness_area'  => $bussiness_area,
                'company_manager' => $request->company_manager,
                'description'     => $request->description,
                'logo'            => $uploadPath,
            ]);

            return $this->success((object)[], 'Company General Data updated', 200);
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
        )->where('id', $id)->first();

        if (!$company) {
            return $this->error(null, 'Company not found', 404);
        }

        // Format logo
        $company->logo = $company->logo
            ? asset($company->logo)
            : asset('default/default.png');

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
}
