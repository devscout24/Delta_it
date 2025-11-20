<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyPayment;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    use ApiResponse;
    // Index
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
            'year' => 'required|integer|min:2000'
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', $validator->errors()->first(), 422);
        }

        $payments = CompanyPayment::where('company_id', $request->company_id)
            ->where('year', $request->year)
            ->orderBy('month')
            ->get();

        return $this->success($payments, 'Payments fetched successfully', 200);
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
            'year'       => 'required|integer',
            'month'      => 'required|integer|min:1|max:12',
        ]);

        if ($validation->fails()) {
            return $this->error('Validation Error', $validation->errors()->first(), 422);
        }

        $payment = CompanyPayment::firstOrCreate(
            [
                'company_id' => $request->company_id,
                'year'       => $request->year,
                'month'      => $request->month,
            ],
            ['status' => 'unpaid']
        );

        return $this->success($payment, 'Monthly payment created', 201);
    }
    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            // 'id' => 'required|exists:company_payments,id',
            'value_non_vat' => 'nullable|numeric',
            'value_vat' => 'nullable|numeric',
            'printings_non_vat' => 'nullable|numeric',
            'printings_vat' => 'nullable|numeric',
            'status' => 'nullable|in:paid,unpaid',
        ]);

        if ($validation->fails()) {
            return $this->error('Validation Error', $validation->errors()->first(), 422);
        }

        $payment = CompanyPayment::find($id);

        $payment->update([
            'value_non_vat' => $request->value_non_vat ?? $payment->value_non_vat,
            'value_vat' => $request->value_vat ?? $payment->value_vat,
            'printings_non_vat' => $request->printings_non_vat ?? $payment->printings_non_vat,
            'printings_vat' => $request->printings_vat ?? $payment->printings_vat,
            'total_vat' => ($request->value_vat ?? $payment->value_vat) + ($request->printings_vat ?? $payment->printings_vat),
            'total_amount' => ($request->value_non_vat ?? $payment->value_non_vat)
                + ($request->value_vat ?? $payment->value_vat)
                + ($request->printings_non_vat ?? $payment->printings_non_vat)
                + ($request->printings_vat ?? $payment->printings_vat),
            'status' => $request->status ?? $payment->status,
        ]);

        return $this->success($payment, 'Payment updated successfully', 200);
    }

    public function dataInfo(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
            'year'       => 'required|integer',
        ]);

        if ($validation->fails()) {
            return $this->error('Validation Error', $validation->errors()->first(), 422);
        }

        $query = CompanyPayment::where('company_id', $request->company_id)
            ->where('year', $request->year);

        return $this->success([
            'total_paid' => $query->where('status', 'paid')->sum('total_amount'),
            'total_unpaid' => $query->where('status', 'unpaid')->sum('total_amount'),
            'months' => $query->orderBy('month')->get(),
        ], 'Yearly info fetched successfully', 200);
    }

    public function allPaymentsInfo(Request $request)
    {
        // Validate filters
        $request->validate([
            'year'   => 'nullable|digits:4',
            'month'  => 'nullable|string',
            'status' => 'nullable|in:paid,unpaid,all',
            'name'   => 'nullable|string'
        ]);

        $status = $request->status ?? 'all';

        $query = CompanyPayment::with('company:id,name');

        // Filter by Year
        if ($request->year) {
            $query->whereYear('created_at', $request->year);
        }

        // Filter by Month ("January", "02", "Jan", "3" → all handled)
        if ($request->month) {
            $monthNumber = $this->convertMonthToNumber($request->month);
            if ($monthNumber) {
                $query->whereMonth('created_at', $monthNumber);
            }
        }

        // Filter by Status (skip when 'all')
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Filter by Company Name
        if ($request->name) {
            $query->whereHas('company', function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->name . '%');
            });
        }

        $payments = $query->orderBy('created_at', 'desc')->get();

        return $this->success($payments, "Payments fetched successfully", 200);
    }

    private function convertMonthToNumber($month)
    {
        // If numeric like "1" or "09"
        if (is_numeric($month)) {
            return str_pad($month, 2, '0', STR_PAD_LEFT);
        }

        // If text like "December" or "Dec"
        $m = date_parse($month);
        return $m['month'] ?? null;
    }
}
