<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\Company;

class CompanyController extends Controller
{
    use ApiResponse;

    // ======================
    // LIST COMPANIES (TABLE)
    // ======================
    public function index(Request $request)
    {
        $query = Company::query();

        // Active / Archived filter
        if ($request->get('type') === 'archived') {
            $query->where('is_active', 0);
        } else {
            $query->where('is_active', 1);
        }

        // Search
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Incubation filter (IMPORTANT)
        if ($request->filled('incubation_type')) {
            $query->where('incubation_type', $request->incubation_type);
        }

        // Optional sorting
        if ($request->filled('sort_by') && $request->filled('sort_order')) {
            $query->orderBy($request->sort_by, $request->sort_order);
        } else {
            $query->latest();
        }

        $companies = $query->paginate(10);

        $data = $companies->getCollection()->map(function ($c) {
            return [
                'id' => $c->id,
                'name' => $c->name,
                'email' => $c->email,
                'incubation_type' => $c->incubation_type,
                'renewal_date' => $c->renewal_date,
                'end_date' => $c->end_date,
                'pending_requests' => $c->pending_requests ?? 0,
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
    // DROPDOWN LIST (ROOM MODULE)
    // ======================
    public function list()
    {
        $companies = Company::where('is_active', 1)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return $this->success($companies, 'Company list fetched');
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
    // UPDATE COMPANY
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
        $validator = Validator::make($request->all(), [
            'logo' => 'required|image|mimes:png,jpeg,jpg|max:15360'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $company = Company::find($id);

        if (!$company) {
            return $this->error([], 'Company not found', 404);
        }

        // delete old logo if exists
        if ($company->logo && Storage::disk('public')->exists(str_replace('storage/', '', $company->logo))) {
            Storage::disk('public')->delete(str_replace('storage/', '', $company->logo));
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

        if ($company->logo && Storage::disk('public')->exists(str_replace('storage/', '', $company->logo))) {
            Storage::disk('public')->delete(str_replace('storage/', '', $company->logo));
        }

        $company->update(['logo' => null]);

        return $this->success([], 'Logo removed');
    }

    // ======================
    // ARCHIVE COMPANY
    // ======================
    public function archive($id)
    {
        $company = Company::find($id);

        if (!$company) {
            return $this->error([], 'Company not found', 404);
        }

        $company->update(['is_active' => 0]);

        return $this->success([], 'Company archived');
    }

    // ======================
    // RESTORE COMPANY
    // ======================
    public function restore($id)
    {
        $company = Company::find($id);

        if (!$company) {
            return $this->error([], 'Company not found', 404);
        }

        $company->update(['is_active' => 1]);

        return $this->success([], 'Company restored');
    }
}
