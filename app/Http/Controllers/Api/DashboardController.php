<?php

namespace App\Http\Controllers\Api;

use App\Models\Room;
use App\Models\Company;
use App\Traits\ApiResponse;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    use ApiResponse;
    public function getMetrics()
    {
        $activeCompanies = Company::where('status', 'active')->count();
        $occupiedRooms = Room::where('status', 'occupied')->count();

        return $this->success([
            'active_companies' => $activeCompanies,
            'occupation' =>  $occupiedRooms,

        ], 'Dashboard metrics fetched successfully', 200);
    }
}
