<?php

namespace App\Http\Controllers\Api;

use App\Models\Company;
use App\Models\CompanyPayment;
use App\Models\RoomBookRequest;
use App\Models\MettingBookRequest;
use App\Models\Contract;
use App\Models\MeetingBooking;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Carbon\Carbon;

class DashboardController extends Controller
{
    use ApiResponse;
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

    public function stats()
    {
        $now = Carbon::now();
        $year = $now->year;
        $month = $now->month;

        // Active companies
        $totalActive = Company::where('status', 'active')->count();
        $newActiveThisMonth = Company::where('status', 'active')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count();

        // Active companies by month for current year
        $activeByMonthRaw = Company::where('status', 'active')
            ->whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->groupByRaw('MONTH(created_at)')
            ->pluck('count','month')
            ->toArray();

        $activeByMonth = [];
        for ($m = 1; $m <= 12; $m++) {
            $activeByMonth[$m] = isset($activeByMonthRaw[$m]) ? (int) $activeByMonthRaw[$m] : 0;
        }

        // Requests
        $roomRequestsCount = RoomBookRequest::count();
        $meetingRequestsCount = MettingBookRequest::count();

        // Pending payments
        $pendingPayments = CompanyPayment::where('status', 'pending')->count();

        // Companies this month
        $companiesThisMonth = Company::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count();

        // Company tracking: new this year


        // Company tracking: due this year (by contract renewal_date)
        $contractsDue = Contract::whereYear('renewal_date', $year)->with('company')->get();

        $dueCompanies = $contractsDue->map(function($contract) use ($now) {
            $company = $contract->company;
            $start = $contract->start_date ? Carbon::parse($contract->start_date) : null;
            $renewal = $contract->renewal_date ? Carbon::parse($contract->renewal_date) : null;

            $totalDays = 365;
            if ($start && $renewal && $start->lt($renewal)) {
                $totalDays = max(1, $start->diffInDays($renewal));
            }

            $daysLeft = 0;
            if ($renewal && $renewal->isFuture()) {
                $daysLeft = $now->diffInDays($renewal);
            }

            $percentLeft = (int) round(($daysLeft / $totalDays) * 100);
            $percentLeft = max(0, min(100, $percentLeft));

            return [
                'company_id' => $company?->id,
                'company_name' => $company?->name,
                'company_logo' => asset($company?->logo),
                'contract_id' => $contract->id,
                'contract_name' => $contract->name ?? null,
                'renewal_date' => $contract->renewal_date,
                'percent_left' => $percentLeft,
            ];
        })->toArray();

        $newCompanies = Company::whereYear('created_at', $year)->get();

        $newCompanies = $newCompanies->map(function($company) use ($now) {
            $daysLeft = $now->diffInDays($company->created_at);
            return [
                'id' => $company->id,
                'name' => $company->name,
                'logo' => asset($company->logo),
                'created_at' => $company->created_at?->toDateString(),
                'days_since_creation' => $daysLeft,
            ];
        });

        // Upcoming meetings (next 14 days)
        $upcomingMeetings = MeetingBooking::whereBetween('booking_date', [$now->startOfDay(), $now->copy()->addDays(14)->endOfDay()])
            ->with(['room','company'])
            ->orderBy('booking_date', 'asc')
            ->get()
            ->map(function($m) {
                return [
                    'id' => $m->id,
                    'booking_name' => $m->booking_name,
                    'booking_date' => $m->booking_date?->toDateString(),
                    'room' => $m->room?->room_name,
                    'company' => $m->company?->name,
                    'status' => $m->status,
                ];
            })->toArray();

        $data = [
            'active_companies_total'          => $totalActive,
            'active_companies_new_this_month' => $newActiveThisMonth,
            'active_companies_by_month'       => $activeByMonth,
            'room_requests_count'             => $roomRequestsCount,
            'meeting_requests_count'          => $meetingRequestsCount,
            'pending_payments_count'          => $pendingPayments,
            'companies_this_month'            => $companiesThisMonth,
            'company_tracking'                => [
                'new_this_year' => $newCompanies,
                'due_this_year' => $dueCompanies,
            ],
            'upcoming_meetings' => $upcomingMeetings,
        ];

        return $this->success($data , 'Dashboard data fetched successfully', 200);
    }
}
