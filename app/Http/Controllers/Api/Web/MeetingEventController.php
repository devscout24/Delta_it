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
    // LIST EVENTS
    // ======================
    public function index()
    {
        return $this->success(MeetingEvent::latest()->get(), 'Events fetched');
    }

    // ======================
    // CREATE EVENT
    // ======================
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'type' => 'required|string',
            'duration' => 'required|integer',
            'location' => 'nullable|string',
            'meeting_link' => 'nullable|string',
            'max_invites' => 'nullable|integer',
            'description' => 'nullable|string',
            'color' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $data = $validator->validated();

        return $this->success(
            MeetingEvent::create($data),
            'Event created'
        );
    }

    // ======================
    // SHOW EVENT
    // ======================
    public function show($id)
    {
        $event = MeetingEvent::with('schedules.days')->find($id);

        if (!$event) {
            return $this->error([], 'Not found', 404);
        }

        return $this->success($event, 'Details');
    }

    // ======================
    // UPDATE EVENT
    // ======================
    public function update(Request $request, $id)
    {
        $event = MeetingEvent::find($id);

        if (!$event) {
            return $this->error([], 'Not found', 404);
        }

        $event->update($request->only([
            'title',
            'type',
            'duration',
            'location',
            'meeting_link',
            'max_invites',
            'description',
            'color'
        ]));

        return $this->success($event, 'Updated');
    }

    // ======================
    // ADD SCHEDULE + AUTO SLOT
    // ======================
    public function addSchedule(Request $request, $eventId)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',

            'days' => 'required|array',
            'days.*.day' => 'required|string',
            'days.*.start_time' => 'required',
            'days.*.end_time' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $event = MeetingEvent::find($eventId);

        if (!$event) {
            return $this->error([], 'Event not found', 404);
        }

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
    // GET SLOTS
    // ======================
    public function slots(Request $request, $id)
    {
        $request->validate([
            'date' => 'required|date'
        ]);

        $slots = MeetingEventSlot::where('event_id', $id)
            ->where('date', $request->date)
            ->orderBy('start_time')
            ->get();

        return $this->success($slots, 'Slots');
    }

    // ======================
    // REQUEST LIST
    // ======================
    public function requests()
    {
        $requests = MeetingBooking::with('event')
            ->latest()
            ->get();

        return $this->success($requests, 'Requests');
    }

    // ======================
    // APPROVE
    // ======================
    public function approve($id)
    {
        $booking = MeetingBooking::find($id);

        if (!$booking) {
            return $this->error([], 'Not found', 404);
        }

        DB::beginTransaction();

        try {
            // prevent double booking
            $alreadyBooked = MeetingBooking::where([
                'event_id' => $booking->event_id,
                'date' => $booking->date,
                'start_time' => $booking->start_time,
                'status' => 'approved'
            ])->exists();

            if ($alreadyBooked) {
                return $this->error([], 'Slot already taken', 422);
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

    // ======================
    // REJECT
    // ======================
    public function reject($id)
    {
        $booking = MeetingBooking::find($id);

        if (!$booking) {
            return $this->error([], 'Not found', 404);
        }

        $booking->update(['status' => 'rejected']);

        return $this->success([], 'Rejected');
    }
}
