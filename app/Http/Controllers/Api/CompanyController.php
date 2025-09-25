<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Traits\ApiResponse;
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

        return $this->success((object)[], 'Company Added Successful');
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
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        if (!$request->id) {
            return $this->error('', 'Id not sent', 404);
        }

        $occpaied_office = json_encode($request->occupied_office);
        $bussiness_area = json_encode($request->bussiness_area);


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

    public function getAllCompanies()
    {

        $companies = Company::all();

        $data = [];
        foreach ($companies as $company) {
            $data[] = [
                'id' => $company->id,
                'commercial_name' => $company->commercial_name,
                'company_email' => $company->company_email,
            ];
        }

        return $this->success($companies, 'Companies fetched successful', 200);
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
}
