<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Ticket;

class RequestController extends Controller
{
    use ApiResponse;

    // ======================
    // LIST COMPANY REQUESTS
    // ======================
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $query = Ticket::with(['user'])
            ->where('company_id', $request->company_id);

        // optional filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $tickets = $query->latest()->paginate(10);

        $data = $tickets->getCollection()->map(function ($t) {
            return [
                'id' => $t->id,
                'description' => $t->subject, // or description field if exists
                'date' => $t->created_at->format('d/m/Y'),

                'requested_by' => $t->user?->name ?? 'Unknown',

                'status' => $this->formatStatus($t->status),
            ];
        });

        return $this->success([
            'requests' => $data,
            'pagination' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'total' => $tickets->total(),
            ]
        ], 'Requests fetched');
    }

    // ======================
    // FORMAT STATUS FOR UI
    // ======================
    private function formatStatus($status)
    {
        return match ($status) {
            'pending' => 'Pending',
            'in_progress' => 'In progress',
            'resolved' => 'Solved and closed',
            'closed' => 'Closed',
            default => ucfirst($status),
        };
    }
}
