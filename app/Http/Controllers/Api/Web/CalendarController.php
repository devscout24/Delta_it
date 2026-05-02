<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\MeetingEventSchedule;
use App\Models\MeetingEventSlot;
use App\Models\MeetingBooking;
use App\Models\MeetingEvent;

class CalendarController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'view'       => 'nullable|in:day,week,month',
            'date'       => 'nullable|date_format:Y-m-d',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date'   => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return $this->error([
                'errors' => $validator->errors(),
            ], 'Validation failed', 422);
        }

        $view = $request->filled('view') ? $request->input('view') : 'month';
        $baseDate = Carbon::parse($request->filled('date') ? $request->input('date') : now()->toDateString());

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
        } else {
            [$startDate, $endDate] = $this->resolveRangeByView($view, $baseDate);
        }

        // Schedules (these are the event occurrence dates)
        $schedules = MeetingEventSchedule::with('event')
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('date')
            ->get();

        // Bookings (actual booked slots)
        $bookings = MeetingBooking::with('event')
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $calendarItems = collect()
            ->merge($schedules->map(function (MeetingEventSchedule $s) {
                return [
                    'calendar_id' => 'schedule_' . $s->id,
                    'type' => 'event_schedule',
                    'id' => $s->id,
                    'title' => $s->event?->title ?? 'Event',
                    'date' => $s->date,
                    'start_time' => null,
                    'end_time' => null,
                    'start_at' => $s->date . 'T00:00:00',
                    'end_at' => $s->date . 'T23:59:59',
                    'all_day' => true,
                    'color' => $s->event?->color ?? '#888',
                    'status' => null,
                    'location' => $s->event?->location,
                ];
            }))
            ->merge($bookings->map(function (MeetingBooking $b) {
                $startAt = $b->date . 'T' . ($b->start_time ?? '00:00:00');
                $endAt = $b->date . 'T' . ($b->end_time ?? '23:59:59');

                return [
                    'calendar_id' => 'booking_' . $b->id,
                    'type' => 'booking',
                    'id' => $b->id,
                    'title' => $b->name ?? ($b->event?->title ?? 'Booking'),
                    'date' => $b->date,
                    'start_time' => $b->start_time,
                    'end_time' => $b->end_time,
                    'start_at' => $startAt,
                    'end_at' => $endAt,
                    'all_day' => false,
                    'color' => '#D0021B',
                    'status' => $b->status,
                    'location' => $b->event?->location,
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
                'schedules' => $schedules->count(),
                'bookings' => $bookings->count(),
                'total' => $calendarItems->count(),
            ],
            'items' => $calendarItems,
            'schedules' => $schedules,
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
