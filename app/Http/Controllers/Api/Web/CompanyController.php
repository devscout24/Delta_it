<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Company;

class CompanyController extends Controller
{
    use ApiResponse;

    // ======================
    // LIST COMPANIES
    // ======================
    public function index(Request $request)
    {
        $query = Company::query();

        // Filter: active / archived
        if ($request->get('type') === 'archived') {
            $query->where('is_active', 0);
        } else {
            $query->where('is_active', 1);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $companies = $query->latest()->paginate(10);

        $data = $companies->getCollection()->map(function ($c) {
            return [
                'id' => $c->id,
                'name' => $c->name,
                'email' => $c->email,
                'incubation_type' => $c->incubation_type,
                'renewal_date' => $c->renewal_date,
                'end_date' => $c->end_date,
                'pending_requests' => $c->pending_requests ?? 0, // optional
            ];
        });

        return $this->success([
            'companies' => $data,
            'pagination' => [
                'current_page' => $companies->currentPage(),
                'last_page' => $companies->lastPage(),
                'total' => $companies->total(),
            ]
        ], 'Companies fetched');
    }

    // ======================
    // CREATE COMPANY
    // ======================
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:companies,email',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $company = Company::create([
            'name' => $request->name,
            'email' => $request->email,
            'is_active' => 1,
        ]);

        return $this->success([
            'id' => $company->id,
            'name' => $company->name,
        ], 'Company created', 201);
    }

    // ======================
    // SHOW COMPANY (EDIT PAGE)
    // ======================
    public function show($id)
    {
        $company = Company::find($id);

        if (!$company) {
            return $this->error([], 'Company not found', 404);
        }

        return $this->success([
            'id' => $company->id,
            'name' => $company->name,
            'email' => $company->email,
            'fiscal_name' => $company->fiscal_name,
            'nif' => $company->nif,
            'phone' => $company->phone,
            'incubation_type' => $company->incubation_type,
            'business_area' => $company->business_area,
            'manager' => $company->manager,
            'description' => $company->description,
            'logo' => $company->logo ? asset($company->logo) : null,
        ], 'Company fetched');
    }

    // ======================
    // UPDATE GENERAL DATA
    // ======================
    public function update(Request $request, $id)
    {
        $company = Company::find($id);

        if (!$company) {
            return $this->error([], 'Company not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:companies,email,' . $id,
            'fiscal_name' => 'nullable|string',
            'nif' => 'nullable|string',
            'phone' => 'nullable|string',
            'incubation_type' => 'nullable|string',
            'business_area' => 'nullable',
            'manager' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $company->update($request->only([
            'name',
            'email',
            'fiscal_name',
            'nif',
            'phone',
            'incubation_type',
            'business_area',
            'manager',
            'description'
        ]));

        return $this->success([], 'Company updated');
    }

    // ======================
    // UPLOAD LOGO
    // ======================
    public function uploadLogo(Request $request, $id)
    {
        $request->validate([
            'logo' => 'required|image|max:2048'
        ]);

        $company = Company::find($id);

        if (!$company) {
            return $this->error([], 'Company not found', 404);
        }

        $file = $request->file('logo');
        $path = $file->store('company_logos', 'public');

        $company->update([
            'logo' => 'storage/' . $path
        ]);

        return $this->success([
            'logo' => asset($company->logo)
        ], 'Logo uploaded');
    }

    // ======================
    // DELETE LOGO
    // ======================
    public function deleteLogo($id)
    {
        $company = Company::find($id);

        if (!$company) {
            return $this->error([], 'Company not found', 404);
        }

        $company->update(['logo' => null]);

        return $this->success([], 'Logo removed');
    }

    // ======================
    // ARCHIVE
    // ======================
    public function archive($id)
    {
        $company = Company::find($id);

        if (!$company) {
            return $this->error([], 'Company not found', 404);
        }

        $company->update([
            'is_active' => !$company->is_active
        ]);

        return $this->success([], 'Status updated');
    }

    public function deactivate($id)
    {
        $company = Company::find($id);

        if (!$company) {
            return $this->error([], 'Company not found', 404);
        }

        $company->update(['is_active' => false]);

        return $this->success([], 'Company deactivated');
    }

    public function activate($id)
    {
        $company = Company::find($id);

        if (!$company) {
            return $this->error([], 'Company not found', 404);
        }

        $company->update(['is_active' => true]);

        return $this->success([], 'Company activated');
    }

    public function destroy($id)
    {
        $company = Company::find($id);

        if (!$company) {
            return $this->error([], 'Company not found', 404);
        }

        $company->delete();

        return $this->success([], 'Company deleted');
    }
}
