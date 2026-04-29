<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\CompanyPayment;

class CompanyPaymentController extends Controller
{
    use ApiResponse;

    // ======================
    // LIST BY YEAR
    // ======================
    public function index(Request $request)
    {
        $request->validate([
            'company_id' => 'required',
            'year' => 'required'
        ]);

        $payments = CompanyPayment::where('company_id', $request->company_id)
            ->where('year', $request->year)
            ->orderBy('month')
            ->get();

        return $this->success($payments, 'Payments fetched');
    }

    // ======================
    // INIT YEAR (12 MONTHS)
    // ======================
    public function initYear(Request $request)
    {
        $request->validate([
            'company_id' => 'required',
            'year' => 'required'
        ]);

        for ($i = 1; $i <= 12; $i++) {
            CompanyPayment::firstOrCreate([
                'company_id' => $request->company_id,
                'year' => $request->year,
                'month' => $i
            ]);
        }

        return $this->success([], 'Year initialized');
    }

    // ======================
    // UPDATE MONTH
    // ======================
    public function update(Request $request, $id)
    {
        $payment = CompanyPayment::find($id);

        if (!$payment) {
            return $this->error([], 'Payment not found', 404);
        }

        $payment->update([
            'value_non_vat' => $request->value_non_vat,
            'value_vat' => $request->value_vat,
            'printings_non_vat' => $request->printings_non_vat,
            'printings_vat' => $request->printings_vat,
            'status' => $request->status,
            'total_amount' => ($request->value_non_vat ?? 0) +
                ($request->value_vat ?? 0) +
                ($request->printings_non_vat ?? 0) +
                ($request->printings_vat ?? 0),
        ]);

        return $this->success($payment, 'Updated');
    }

    // ======================
    // SUMMARY
    // ======================
    public function summary(Request $request)
    {
        $request->validate([
            'company_id' => 'required',
            'year' => 'required'
        ]);

        $payments = CompanyPayment::where('company_id', $request->company_id)
            ->where('year', $request->year)
            ->get();

        $paid = $payments->where('status', 'paid')->sum('total_amount');
        $unpaid = $payments->where('status', 'unpaid')->sum('total_amount');

        return $this->success([
            'total_paid' => $paid,
            'total_unpaid' => $unpaid
        ], 'Summary fetched');
    }
}
