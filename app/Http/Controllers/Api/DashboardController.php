<?php

namespace App\Http\Controllers\Api;

use App\Models\Company;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function getMetrics()
    {
        $activeCompanies = Company::where('status', 'active')->count();
        $pendingRequests = Request::where('status', 'pending')->count();
        // $pendingPayments = Payment::where('status', 'pending')->count();


        $occupationPercentage = 40;

        return response()->json([
            'active_companies' => $activeCompanies,
            'pending_requests' => $pendingRequests,
            'occupation' => $occupationPercentage,
            // 'pending_payments' => $pendingPayments,
        ]);
    }
}
