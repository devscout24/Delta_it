<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use App\Models\MeetingEvent;
use App\Models\MeetingEventSchedule;
use App\Models\MeetingEventScheduleDay;
use App\Models\MeetingEventSlot;
use App\Models\MeetingBooking;

class MeetingEventController extends Controller
{
    use ApiResponse;

    // ======================
    // EVENTS
    // ======================

    public function index(Request $request)
    {
        $query = MeetingEvent::query();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('location')) {
            $query->where('location', $request->location);
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        return $this->success($query->latest()->get(), 'Events fetched');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string',
            'type' => 'required|in:virtual,physical',
            'location' => 'nullable|string',
            'meeting_link' => 'nullable|string',
            'duration' => 'required|integer|min:1',
            'max_invitees' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'color' => 'nullable|string',
            'timezone' => 'nullable|string'
        ]);

        return $this->success(
            MeetingEvent::create($data),
            'Event created'
        );
    }

    public function show($id)
    {
        $event = MeetingEvent::with('schedules.days')->find($id);

        if (!$event) {
            return $this->error([], 'Event not found', 404);
        }

        return $this->success($event, 'Event details');
    }

    public function update(Request $request, $id)
    {
        $event = MeetingEvent::find($id);

        if (!$event) {
            return $this->error([], 'Not found', 404);
        }

        $event->update($request->all());

        return $this->success($event, 'Updated');
    }

    public function destroy($id)
    {
        $event = MeetingEvent::find($id);

        if (!$event) {
            return $this->error([], 'Not found', 404);
        }

        $event->delete();

        return $this->success([], 'Deleted');
    }

    // ======================
    // SCHEDULING
    // ======================

    public function addSchedule(Request $request, $eventId)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'days' => 'required|array',
            'days.*.day' => 'required|string',
            'days.*.start_time' => 'required',
            'days.*.end_time' => 'required',
        ]);

        $event = MeetingEvent::findOrFail($eventId);

        DB::beginTransaction();

        try {
            $schedule = MeetingEventSchedule::create([
                'meeting_event_id' => $eventId,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);

            foreach ($request->days as $day) {
                MeetingEventScheduleDay::create([
                    'schedule_id' => $schedule->id,
                    'day_of_week' => strtolower($day['day']),
                    'start_time' => $day['start_time'],
                    'end_time' => $day['end_time'],
                ]);
            }

            $this->generateSlots($event, $schedule, $request->days);

            DB::commit();

            return $this->success([], 'Schedule created');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error([], $e->getMessage(), 500);
        }
    }

    public function schedules($id)
    {
        $data = MeetingEventSchedule::with('days')
            ->where('meeting_event_id', $id)
            ->get();

        return $this->success($data, 'Schedules');
    }

    public function deleteSchedule($id)
    {
        MeetingEventSchedule::findOrFail($id)->delete();
        return $this->success([], 'Deleted');
    }

    // ======================
    // SLOT GENERATION
    // ======================

    private function generateSlots($event, $schedule, $days)
    {
        $start = Carbon::parse($schedule->start_date);
        $end = Carbon::parse($schedule->end_date);

        while ($start->lte($end)) {

            $dayName = strtolower($start->format('l'));

            foreach ($days as $day) {

                if ($dayName === strtolower($day['day'])) {

                    $current = Carbon::parse($day['start_time']);
                    $endTime = Carbon::parse($day['end_time']);

                    while ($current->lt($endTime)) {

                        $slotEnd = $current->copy()->addMinutes($event->duration);

                        if ($slotEnd->gt($endTime)) break;

                        $exists = MeetingEventSlot::where([
                            'event_id' => $event->id,
                            'date' => $start->toDateString(),
                            'start_time' => $current->format('H:i')
                        ])->exists();

                        if (!$exists) {
                            MeetingEventSlot::create([
                                'event_id' => $event->id,
                                'date' => $start->toDateString(),
                                'start_time' => $current->format('H:i'),
                                'end_time' => $slotEnd->format('H:i'),
                                'is_booked' => false
                            ]);
                        }

                        $current->addMinutes($event->duration);
                    }
                }
            }

            $start->addDay();
        }
    }

    // ======================
    // SLOTS
    // ======================

    public function slots(Request $request, $id)
    {
        $request->validate(['date' => 'required|date']);

        $slots = MeetingEventSlot::where('event_id', $id)
            ->where('date', $request->date)
            ->orderBy('start_time')
            ->get();

        return $this->success($slots, 'Slots');
    }

    public function blockSlot(Request $request)
    {
        $request->validate([
            'event_id' => 'required',
            'date' => 'required',
            'start_time' => 'required'
        ]);

        MeetingEventSlot::where($request->only('event_id', 'date', 'start_time'))
            ->update(['is_booked' => true]);

        return $this->success([], 'Blocked');
    }

    // ======================
    // CALENDAR
    // ======================

    public function calendar(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'view' => 'required|in:day,week,month'
        ]);

        $date = Carbon::parse($request->date);

        $start = $date->copy()->startOfMonth();
        $end = $date->copy()->endOfMonth();

        $data = MeetingEventSlot::whereBetween('date', [$start, $end])->get();

        return $this->success($data, 'Calendar');
    }

    public function quickBook(Request $request)
    {
        $request->validate([
            'event_id' => 'required',
            'date' => 'required|date',
            'start_time' => 'required'
        ]);

        $booking = MeetingBooking::create([
            'event_id' => $request->event_id,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'status' => 'approved'
        ]);

        MeetingEventSlot::where($request->only('event_id', 'date', 'start_time'))
            ->update(['is_booked' => true]);

        return $this->success($booking, 'Booked');
    }

    // ======================
    // REQUESTS
    // ======================

    public function requests()
    {
        return $this->success(
            MeetingBooking::with('event')->latest()->get(),
            'Requests'
        );
    }

    public function requestDetails($id)
    {
        return $this->success(
            MeetingBooking::with('event')->findOrFail($id),
            'Details'
        );
    }

    public function approve($id)
    {
        $booking = MeetingBooking::findOrFail($id);

        DB::beginTransaction();

        try {
            $exists = MeetingBooking::where([
                'event_id' => $booking->event_id,
                'date' => $booking->date,
                'start_time' => $booking->start_time,
                'status' => 'approved'
            ])->exists();

            if ($exists) {
                return $this->error([], 'Already booked', 422);
            }

            $booking->update(['status' => 'approved']);

            MeetingEventSlot::where([
                'event_id' => $booking->event_id,
                'date' => $booking->date,
                'start_time' => $booking->start_time
            ])->update(['is_booked' => true]);

            DB::commit();

            return $this->success([], 'Approved');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error([], $e->getMessage(), 500);
        }
    }

    public function reject($id)
    {
        MeetingBooking::findOrFail($id)->update(['status' => 'rejected']);
        return $this->success([], 'Rejected');
    }

    // ======================
    // SUPPORT
    // ======================

    public function locations()
    {
        return $this->success(
            MeetingEvent::select('location')->distinct()->pluck('location'),
            'Locations'
        );
    }

    public function types()
    {
        return $this->success(['virtual', 'physical'], 'Types');
    }
}
