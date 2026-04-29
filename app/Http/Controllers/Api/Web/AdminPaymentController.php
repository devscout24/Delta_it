<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\CompanyPayment;
use App\Models\Company;

class AdminPaymentController extends Controller
{
    use ApiResponse;

    // ======================
    // TABLE DATA
    // ======================
    public function index(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer'
        ]);

        $query = CompanyPayment::with('company')
            ->where('month', $request->month)
            ->where('year', $request->year);

        // search company
        if ($request->filled('search')) {
            $query->whereHas('company', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        $payments = $query->paginate(10);

        $data = $payments->getCollection()->map(function ($p) {
            return [
                'id' => $p->id,

                'company_name' => $p->company?->name,
                'contract_end_date' => $p->company?->end_date,

                'month' => $p->month,

                'value_non_vat' => $p->value_non_vat,
                'value_vat' => $p->value_vat,

                'printings_non_vat' => $p->printings_non_vat,
                'printings_vat' => $p->printings_vat,

                'total' => $p->total_amount,
                'status' => $p->status,
            ];
        });

        return $this->success([
            'data' => $data,
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'total' => $payments->total(),
            ]
        ], 'Payments list');
    }

    // ======================
    // SUMMARY CARDS
    // ======================
    public function summary(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer'
        ]);

        // ---------- MONTH ----------
        $monthly = CompanyPayment::where('month', $request->month)
            ->where('year', $request->year)
            ->get();

        $monthTotal = $monthly->sum('total_amount');
        $monthPaid = $monthly->where('status', 'paid')->sum('total_amount');

        $monthPaidPercent = $monthTotal > 0
            ? round(($monthPaid / $monthTotal) * 100, 2)
            : 0;

        // ---------- YEAR ----------
        $yearly = CompanyPayment::where('year', $request->year)->get();

        $yearTotal = $yearly->sum('total_amount');
        $yearPaid = $yearly->where('status', 'paid')->sum('total_amount');

        $yearPaidPercent = $yearTotal > 0
            ? round(($yearPaid / $yearTotal) * 100, 2)
            : 0;

        return $this->success([
            'month' => [
                'total_invoiced' => $monthTotal,
                'paid_percent' => $monthPaidPercent
            ],
            'year' => [
                'total_invoiced' => $yearTotal,
                'paid_percent' => $yearPaidPercent
            ]
        ], 'Summary fetched');
    }
}
