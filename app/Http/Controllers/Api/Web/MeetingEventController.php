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
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);

            // Generate schedules for each day in the range that matches recurring days
            while ($startDate->lte($endDate)) {
                $dayName = strtolower($startDate->format('l'));

                // Check if this day matches any of the recurring days
                foreach ($request->days as $day) {
                    if ($dayName === strtolower($day['day'])) {
                        $schedule = MeetingEventSchedule::create([
                            'meeting_event_id' => $eventId,
                            'date' => $startDate->toDateString(),
                        ]);

                        MeetingEventScheduleDay::create([
                            'schedule_id' => $schedule->id,
                            'day_of_week' => strtolower($day['day']),
                            'start_time' => $day['start_time'],
                            'end_time' => $day['end_time'],
                        ]);

                        $this->generateSlotsForSchedule($event, $schedule, $day);
                        break;
                    }
                }

                $startDate->addDay();
            }

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

    private function generateSlotsForSchedule($event, $schedule, $dayInfo)
    {
        $start = Carbon::createFromFormat('H:i:s', $dayInfo['start_time']);
        $end = Carbon::createFromFormat('H:i:s', $dayInfo['end_time']);

        while ($start->lt($end)) {
            $slotEnd = $start->copy()->addMinutes($event->duration);

            if ($slotEnd->gt($end)) break;

            $exists = MeetingEventSlot::where([
                'meeting_event_schedule_id' => $schedule->id,
                'start_time' => $start->format('H:i:s'),
            ])->exists();

            if (!$exists) {
                MeetingEventSlot::create([
                    'meeting_event_schedule_id' => $schedule->id,
                    'start_time' => $start->format('H:i:s'),
                    'end_time' => $slotEnd->format('H:i:s'),
                    'is_booked' => false
                ]);
            }

            $start->addMinutes($event->duration);
        }
    }

    // ======================
    // SLOTS
    // ======================

    public function slots(Request $request, $id)
    {
        $request->validate(['date' => 'required|date']);

        $slots = MeetingEventSlot::join('meeting_event_schedules', 'meeting_event_slots.meeting_event_schedule_id', '=', 'meeting_event_schedules.id')
            ->where('meeting_event_schedules.meeting_event_id', $id)
            ->whereDate('meeting_event_schedules.date', $request->date)
            ->select('meeting_event_slots.*')
            ->orderBy('meeting_event_slots.start_time')
            ->get();

        return $this->success($slots, 'Slots');
    }

    public function blockSlot(Request $request)
    {
        $request->validate([
            'event_id' => 'required',
            'schedule_id' => 'required',
            'start_time' => 'required'
        ]);

        MeetingEventSlot::where([
            'meeting_event_schedule_id' => $request->schedule_id,
            'start_time' => $request->start_time
        ])->update(['is_booked' => true]);

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

        $data = MeetingEventSchedule::whereBetween('date', [$start, $end])
            ->with('slots')
            ->get();

        return $this->success($data, 'Calendar');
    }

    public function quickBook(Request $request)
    {
        $request->validate([
            'schedule_id' => 'required',
            'start_time' => 'required'
        ]);

        $schedule = MeetingEventSchedule::findOrFail($request->schedule_id);

        $slot = MeetingEventSlot::where([
            'meeting_event_schedule_id' => $request->schedule_id,
            'start_time' => $request->start_time
        ])->first();

        $booking = MeetingBooking::create([
            'event_id' => $schedule->meeting_event_id,
            'date' => $schedule->date,
            'start_time' => $request->start_time,
            'end_time' => $slot?->end_time,
            'status' => 'approved'
        ]);

        if ($slot) {
            $slot->update(['is_booked' => true]);
        }

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
            ])->where('id', '!=', $id)->exists();

            if ($exists) {
                return $this->error([], 'Slot already booked', 422);
            }

            $booking->update(['status' => 'approved']);

            MeetingEventSlot::join('meeting_event_schedules', 'meeting_event_slots.meeting_event_schedule_id', '=', 'meeting_event_schedules.id')
                ->where('meeting_event_schedules.meeting_event_id', $booking->event_id)
                ->where('meeting_event_schedules.date', $booking->date)
                ->where('meeting_event_slots.start_time', $booking->start_time)
                ->update(['meeting_event_slots.is_booked' => true]);

            DB::commit();

            return $this->success([], 'Approved');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error([], $e->getMessage(), 500);
        }
    }

    public function reject($id)
    {
        $booking = MeetingBooking::findOrFail($id);

        DB::beginTransaction();

        try {

            $booking->update(['status' => 'rejected']);

            MeetingEventSlot::join('meeting_event_schedules', 'meeting_event_slots.meeting_event_schedule_id', '=', 'meeting_event_schedules.id')
                ->where('meeting_event_schedules.meeting_event_id', $booking->event_id)
                ->where('meeting_event_schedules.date', $booking->date)
                ->where('meeting_event_slots.start_time', $booking->start_time)
                ->update(['meeting_event_slots.is_booked' => false]);

            DB::commit();

            return $this->success([], 'Rejected');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error([], $e->getMessage(), 500);
        }
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
