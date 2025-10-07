<?php

namespace App\Http\Controllers\Api;

use App\Models\Company;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ArchiveController extends Controller
{
    use ApiResponse;
    public function addToArchive(Request $request)
    {
        // Find company by ID
        $company = Company::find($request->id);

        // If company not found, return error response
        if (! $company) {
            return $this->error('Company not found', [], 404);
        }

        // Update status and save
        $company->status = $request->status;
        $company->save();

        // Return success response
        return $this->success('', 'Company added to archive', 200);
    }


    public function restoreComapany (Request $request){
         // Find company by ID
        $company = Company::find($request->id);

        // If company not found, return error response
        if (! $company) {
            return $this->error('Company not found', [], 404);
        }

        // Update status and save
        $company->status = $request->status;
        $company->save();

        // Return success response
        return $this->success('', 'Company added restore successfully ', 200);
    }


}
