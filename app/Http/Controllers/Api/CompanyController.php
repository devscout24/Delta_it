<?php

namespace App\Http\Controllers\Api;

use App\Models\Company;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class CompanyController extends Controller
{
    use ApiResponse;
    public function addCompany(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'commercial_name' => 'required',
            'company_email' => 'required|email|unique:companies,company_email'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        Company::create([
            'commercial_name' => $request->commercial_name,
            'company_email' => $request->company_email,
        ]);

        $data = [
            'company_id' => Company::latest()->first()->id,
            'commercial_name' => $request->commercial_name,
            'company_email' => $request->company_email,
        ];

        return $this->success($data, 'Company Added Successful');
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


    public function updateCompanyGeneralData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'commercial_name' => 'required',
            'company_email'   => 'required|email|unique:companies,company_email,' . $request->id,
            'fiscal_name'     => 'nullable',
            'nif'             => 'nullable',
            'phone_number'    => 'nullable',
            'incubation_type' => 'nullable',
            'occupied_office' => 'nullable',
            'occupied_area'   => 'nullable',
            'bussiness_area'  => 'nullable',
            'company_manager' => 'nullable',
            'description'     => 'nullable',
            'logo'            => 'nullable',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        if (!$request->id) {
            return $this->error('', 'Id not sent', 404);
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

        Company::Where('id', $request->id)->update([
            'commercial_name' => $request->commercial_name,
            'company_email' => $request->company_email,
            'fiscal_name' => $request->fiscal_name,
            'nif' => $request->nif,
            'phone_number' => $request->phone_number,
            'incubation_type' => $request->incubation_type,
            'occupied_office' => $occpaied_office,
            'occupied_area' => $request->occupied_area,
            'bussiness_area' => $bussiness_area,
            'company_manager' => $request->company_manager,
            'description' => $request->description,
            'logo' => $uploadPath,
        ]);

        return $this->success((object)[], 'Company General Data updated', 201);
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

    public function getAllCompanies(Request $request)
    {
        // Get filter from request, default to all
        $status = $request->query('status'); // 'active' or 'archive'

        // Build query
        $query = Company::with('room', 'contract');

        if ($status) {
            $query->where('status', $status);
        }

        $companies = $query->get();

        $data = [];
        foreach ($companies as $company) {
            $data[] = [
                'id' => $company->id,
                'commercial_name' => $company->commercial_name,
                'incubation_type' => $company->incubation_type,
                'logo' => asset($company->logo),
                'contract' => $company->contract ? [
                    'id' => $company->contract->id,
                    'start_date' => $company->contract->start_date,
                    'end_date' => $company->contract->end_date,
                ] : null,
            ];
        }


        return $this->success($data, 'Companies fetched successfully', 200);
    }


    public function getIncubationTypes()
    {
        $companies = Company::all();
        $data = [];
        foreach ($companies as $company) {
            $data[] = [
                'id' => $company->id,
                'incubation_type' => $company->incubation_type,
            ];
        }

        return $this->success($data, 'Incubation types fetched successfully', 200);
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

    // mobile api

    public function show(Request $request, $id)
    {
        $company  = Company::where('id', $id)->first();

        if (!$company) {
            return $this->error($company, 'Comapany fetched successfully', 201);
        }

        return $this->success($company, 'Comapany fetched successfully', 201);
    }

    public function update(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'commercial_name' => 'required',
            'company_email' => 'required|email',
            'fiscal_name' => 'nullable',
            'nif' => 'nullable',
            'phone_number' => 'nullable',
            'incubation_type' => 'nullable',
            'occupied_office' => 'nullable',
            'occupied_area' => 'nullable',
            'bussiness_area' => 'nullable',
            'company_manager' => 'nullable',
            'description' => 'nullable',
            'logo' => 'nullable',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        if (!$request->id) {
            return $this->error('', 'Id not sent', 404);
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

        Company::Where('id', $request->id)->update([
            'commercial_name' => $request->commercial_name,
            'company_email' => $request->company_email,
            'fiscal_name' => $request->fiscal_name,
            'nif' => $request->nif,
            'phone_number' => $request->phone_number,
            'incubation_type' => $request->incubation_type,
            'occupied_office' => $occpaied_office,
            'occupied_area' => $request->occupied_area,
            'bussiness_area' => $bussiness_area,
            'company_manager' => $request->company_manager,
            'description' => $request->description,
            'logo' => $uploadPath,
        ]);

        return $this->success((object)[], 'Company General Data updated', 201);
    }
}
