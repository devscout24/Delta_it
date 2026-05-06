<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\CompanyPayment;
use Carbon\Carbon;

class CompanyPaymentController extends Controller
{
    use ApiResponse;

    // ======================
    // LIST (ALL 12 MONTHS)
    // ======================
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
            'year'       => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $payments = CompanyPayment::where('company_id', $request->company_id)
            ->where('year', $request->year)
            ->orderBy('month')
            ->get();

        $data = $payments->map(function ($p) {
            return [
                'id'                => $p->id,
                'month'             => $p->month,
                'month_name'        => Carbon::create()->month($p->month)->format('F'),
                'value_non_vat'     => $p->value_non_vat,
                'value_vat'         => $p->value_vat,
                'printings_non_vat' => $p->printings_non_vat,
                'printings_vat'     => $p->printings_vat,
                'total_amount'      => $p->total_amount,
                'status'            => $p->status ?? 'unpaid',
            ];
        });

        return $this->success($data, 'Payments fetched');
    }

    // ======================
    // INIT YEAR
    // ======================
    public function initYear(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
            'year' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        for ($i = 1; $i <= 12; $i++) {
            CompanyPayment::firstOrCreate(
                [
                    'company_id' => $request->company_id,
                    'year' => $request->year,
                    'month' => $i
                ],
                [
                    'status' => 'unpaid'
                ]
            );
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

        $validator = Validator::make($request->all(), [
            'value_non_vat' => 'nullable|numeric',
            'value_vat' => 'nullable|numeric',
            'printings_non_vat' => 'nullable|numeric',
            'printings_vat' => 'nullable|numeric',
            'status' => 'nullable|in:paid,unpaid',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $total =
            ($request->value_non_vat ?? $payment->value_non_vat ?? 0) +
            ($request->value_vat ?? $payment->value_vat ?? 0) +
            ($request->printings_non_vat ?? $payment->printings_non_vat ?? 0) +
            ($request->printings_vat ?? $payment->printings_vat ?? 0);

        $payment->update([
            'value_non_vat' => $request->value_non_vat ?? $payment->value_non_vat,
            'value_vat' => $request->value_vat ?? $payment->value_vat,
            'printings_non_vat' => $request->printings_non_vat ?? $payment->printings_non_vat,
            'printings_vat' => $request->printings_vat ?? $payment->printings_vat,
            'status' => $request->status ?? $payment->status,
            'total_amount' => $total,
        ]);

        return $this->success($payment, 'Updated');
    }

    // ======================
    // SUMMARY
    // ======================
    public function summary(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'year' => 'required|integer'
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
