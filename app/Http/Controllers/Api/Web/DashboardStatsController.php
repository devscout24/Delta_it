<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyPayment;
use App\Models\Contract;
use App\Models\MeetingBooking;
use App\Models\Room;
use App\Models\RoomAllocation;
use App\Models\Ticket;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardStatsController extends Controller
{
    use ApiResponse;

    public function stats(Request $request)
    {
        $today = Carbon::today();
        $windowDays = (int) $request->get('window_days', 7);
        $windowDays = max(1, min($windowDays, 60));
        $windowEnd = $today->copy()->addDays($windowDays);

        $activeCompanies = Company::where('is_active', 1)->count();

        $pendingTicketStatuses = ['open', 'pending', 'in_progress', 'in-progress', 'unsolved'];
        $pendingRequests = Ticket::whereIn('status', $pendingTicketStatuses)->count();

        $totalOperationalRooms = Room::where('status', '!=', 'maintenance')->count();
        $occupiedRooms = RoomAllocation::where('status', 'active')->distinct('room_id')->count('room_id');
        $roomOccupancyPercentage = $totalOperationalRooms > 0
            ? round(($occupiedRooms / $totalOperationalRooms) * 100, 1)
            : 0.0;

        $contractsExpiringSoon = Contract::whereNotNull('end_date')
            ->whereDate('end_date', '>=', $today)
            ->whereDate('end_date', '<=', $today->copy()->addDays(30))
            ->count();

        $upcomingMeetingsCount = MeetingBooking::join('meeting_event_slots', 'meeting_bookings.meeting_event_slot_id', '=', 'meeting_event_slots.id')
            ->join('meeting_event_schedules', 'meeting_event_slots.meeting_event_schedule_id', '=', 'meeting_event_schedules.id')
            ->whereDate('meeting_event_schedules.date', '>=', $today)
            ->whereDate('meeting_event_schedules.date', '<=', $windowEnd)
            ->whereIn('meeting_bookings.status', ['pending', 'approved'])
            ->count();

        $companies = Company::where('is_active', 1)
            ->orderBy('name')
            ->take(8)
            ->get();

        $companyTracking = $companies->map(function (Company $company) use ($pendingTicketStatuses) {
            $latestPayment = CompanyPayment::where('company_id', $company->id)
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->first();

            $activeAllocation = RoomAllocation::with('room')
                ->where('company_id', $company->id)
                ->where('status', 'active')
                ->latest('id')
                ->first();

            $activeContract = Contract::where('company_id', $company->id)
                ->whereIn('status', ['active', 'inactive'])
                ->orderByRaw('CASE WHEN status = "active" THEN 0 ELSE 1 END')
                ->orderByDesc('end_date')
                ->first();

            $companyPendingRequests = Ticket::where('company_id', $company->id)
                ->whereIn('status', $pendingTicketStatuses)
                ->count();

            $contractTag = 'none';
            if ($activeContract?->end_date) {
                $daysLeft = Carbon::parse($activeContract->end_date)->diffInDays(Carbon::today(), false) * -1;

                if ($daysLeft < 0) {
                    $contractTag = 'expired';
                } elseif ($daysLeft <= 30) {
                    $contractTag = 'expiring_soon';
                } else {
                    $contractTag = 'active';
                }
            }

            return [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'payment_status' => $latestPayment?->status ?? 'unpaid',
                'payment_month' => $latestPayment ? sprintf('%02d/%d', $latestPayment->month, $latestPayment->year) : null,
                'room' => $activeAllocation?->room?->name,
                'room_status' => $activeAllocation ? 'occupied' : 'available',
                'pending_requests' => $companyPendingRequests,
                'contract_end_date' => $activeContract?->end_date,
                'contract_status' => $activeContract?->status,
                'contract_tag' => $contractTag,
            ];
        })->values();

        $upcomingMeetings = MeetingBooking::select(
            'meeting_bookings.id',
            'meeting_bookings.status',
            'meeting_event_slots.start_time',
            'meeting_event_slots.end_time',
            'meeting_event_schedules.date'
        )
            ->join('meeting_event_slots', 'meeting_bookings.meeting_event_slot_id', '=', 'meeting_event_slots.id')
            ->join('meeting_event_schedules', 'meeting_event_slots.meeting_event_schedule_id', '=', 'meeting_event_schedules.id')
            ->with('event:id,title,type,location')
            ->whereDate('meeting_event_schedules.date', '>=', $today)
            ->whereDate('meeting_event_schedules.date', '<=', $windowEnd)
            ->orderBy('meeting_event_schedules.date')
            ->orderBy('meeting_event_slots.start_time')
            ->take(8)
            ->get()
            ->map(function (MeetingBooking $booking) {
                return [
                    'id' => $booking->id,
                    'title' => $booking->event?->title ?? 'Meeting',
                    'type' => $booking->event?->type,
                    'location' => $booking->event?->location,
                    'date' => $booking->date,
                    'start_time' => $booking->start_time,
                    'end_time' => $booking->end_time,
                    'status' => $booking->status,
                ];
            })
            ->values();

        return $this->success([
            'cards' => [
                'active_companies' => $activeCompanies,
                'pending_requests' => $pendingRequests,
                'room_occupancy_percentage' => $roomOccupancyPercentage,
                'contracts_expiring_soon' => $contractsExpiringSoon,
                'upcoming_meetings' => $upcomingMeetingsCount,
            ],
            'company_tracking' => $companyTracking,
            'upcoming_meetings' => $upcomingMeetings,
            'meta' => [
                'window_days' => $windowDays,
            ],
        ], 'Dashboard stats fetched');
    }
}
