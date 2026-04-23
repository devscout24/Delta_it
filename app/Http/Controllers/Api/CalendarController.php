<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\MeetingBooking;
use App\Models\MeetingEvent;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CalendarController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'view' => 'nullable|in:day,week,month',
            'date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        $view = $request->input('view', 'month');
        $baseDate = Carbon::parse($request->input('date', now()->toDateString()));

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
        } else {
            [$startDate, $endDate] = $this->resolveRangeByView($view, $baseDate);
        }

        $meetings = Meeting::select(
            'id',
            'meeting_name',
            'date',
            'start_time',
            'end_time',
            'location',
            'online_link',
            'status'
        )
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $events = MeetingEvent::select(
            'id',
            'event_name',
            'event_date',
            'event_color',
            'location',
            'online_link',
            'status'
        )
            ->whereBetween('event_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('event_date')
            ->get();

        $bookings = MeetingBooking::select(
            'id',
            'booking_name',
            'booking_date',
            'booking_color',
            'online_link',
            'status'
        )
            ->whereBetween('booking_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('booking_date')
            ->get();

        $calendarItems = collect()
            ->merge($meetings->map(function ($meeting) {
                return [
                    'calendar_id' => 'meeting_' . $meeting->id,
                    'type' => 'meeting',
                    'id' => $meeting->id,
                    'title' => $meeting->meeting_name,
                    'date' => $meeting->date,
                    'start_time' => $meeting->start_time,
                    'end_time' => $meeting->end_time,
                    'start_at' => $meeting->date . 'T' . ($meeting->start_time ?? '00:00:00'),
                    'end_at' => $meeting->date . 'T' . ($meeting->end_time ?? '23:59:59'),
                    'all_day' => false,
                    'color' => '#4A90E2',
                    'status' => $meeting->status,
                    'location' => $meeting->location,
                    'online_link' => $meeting->online_link,
                ];
            }))
            ->merge($events->map(function ($event) {
                return [
                    'calendar_id' => 'event_' . $event->id,
                    'type' => 'event',
                    'id' => $event->id,
                    'title' => $event->event_name,
                    'date' => $event->event_date,
                    'start_time' => null,
                    'end_time' => null,
                    'start_at' => $event->event_date . 'T00:00:00',
                    'end_at' => $event->event_date . 'T23:59:59',
                    'all_day' => true,
                    'color' => $event->event_color,
                    'status' => $event->status,
                    'location' => $event->location,
                    'online_link' => $event->online_link,
                ];
            }))
            ->merge($bookings->map(function ($booking) {
                return [
                    'calendar_id' => 'booking_' . $booking->id,
                    'type' => 'booking',
                    'id' => $booking->id,
                    'title' => $booking->booking_name,
                    'date' => $booking->booking_date,
                    'start_time' => null,
                    'end_time' => null,
                    'start_at' => $booking->booking_date . 'T00:00:00',
                    'end_at' => $booking->booking_date . 'T23:59:59',
                    'all_day' => true,
                    'color' => $booking->booking_color,
                    'status' => $booking->status,
                    'location' => null,
                    'online_link' => $booking->online_link,
                ];
            }))
            ->sortBy('start_at')
            ->values();

        return $this->success([
            'view' => $view,
            'range' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'summary' => [
                'meetings' => $meetings->count(),
                'events' => $events->count(),
                'bookings' => $bookings->count(),
                'total' => $calendarItems->count(),
            ],
            'items' => $calendarItems,
            'meetings' => $meetings,
            'events' => $events,
            'bookings' => $bookings,
        ], 'Calendar data fetched successfully', 200);
    }

    private function resolveRangeByView(string $view, Carbon $baseDate): array
    {
        if ($view === 'day') {
            return [$baseDate->copy()->startOfDay(), $baseDate->copy()->endOfDay()];
        }

        if ($view === 'week') {
            return [
                $baseDate->copy()->startOfWeek(Carbon::MONDAY),
                $baseDate->copy()->endOfWeek(Carbon::SUNDAY),
            ];
        }

        return [$baseDate->copy()->startOfMonth(), $baseDate->copy()->endOfMonth()];
    }
}
