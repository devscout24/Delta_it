<?php

namespace App\Http\Controllers\Api;

use Log;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\MeetingEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\MeetingBookings;

class EventController extends Controller
{
    use \App\Traits\ApiResponse;
    public function store(Request $request)
    {
        $request->validate([
            'event_name'          => 'required',
            'duration'      => 'required|integer',
            'start_date'    => 'required|date',
            'end_date'      => 'nullable|date',
            'weekly_schedule' => 'required|array'
        ]);

        DB::beginTransaction();

        $event = MeetingEvent::create([
            'user_id'        => auth()->guard('api')->id(),
            'event_name'     => $request->event_name,
            'location'       => $request->location,
            'color'          => $request->color,
            'meeting_link'   => $request->meeting_link,
            'max_invitees'   => $request->max_invitees ?? 1,
            'description'    => $request->description,
            'duration'       => $request->duration,
            'timezone'       => $request->timezone ?? 'UTC',
            'start_date'     => $request->start_date,
            'end_date'       => $request->end_date,
        ]);

        foreach ($request->weekly_schedule as $schedule) {
            $event->schedules()->create([
                'meeting_event_id'   => $event->id,
                'day'        => $schedule['day'],
                'start_time' => $schedule['start_time'],
                'end_time'   => $schedule['end_time'],
            ]);
        }

        DB::commit();

        return response()->json([
            'message' => 'Event created successfully',
            'event' => $event->load('schedules')
        ]);
    }


    public function getAvailableSlots(Request $request)
    {
        $request->validate([
            'event_id'     => 'required|exists:meeting_events,id',
            'selectedDate' => 'nullable|date',
        ]);

        $event = MeetingEvent::with('schedules', 'bookings')->findOrFail($request->event_id);
        $selectedDate = $request->selectedDate;


        if (!$event->duration || $event->duration <= 0) {
            return response()->json([
                'error' => 'Event duration is missing',
                'available_slots' => []
            ]);
        }

        $slots = [];
        $startDate = Carbon::parse($event->start_date);
        $endDate   = $event->end_date ? Carbon::parse($event->end_date) : $startDate->copy()->addWeeks(4);

        $period = CarbonPeriod::create($startDate, $endDate);

        // Convert booked slots into datetime
        $bookedSlots = $event->bookings()->get()->map(function ($booking) {
            return [
                'start' => Carbon::parse($booking->date . ' ' . $booking->slot_start),
                'end'   => Carbon::parse($booking->date . ' ' . $booking->slot_end),
            ];
        });

        foreach ($period as $date) {

            if ($selectedDate && $date->toDateString() !== Carbon::parse($selectedDate)->toDateString()) {
                continue;
            }

            foreach ($event->schedules as $schedule) {



                // ✅ Important: Match DB (sun/mon/tue...)
                if (strtolower($date->format('D')) === strtolower($schedule->day)) {

                    $slotStart = Carbon::createFromFormat('H:i:s', $schedule->start_time)
                        ->setDate($date->year, $date->month, $date->day);

                    $slotEnd = Carbon::createFromFormat('H:i:s', $schedule->end_time)
                        ->setDate($date->year, $date->month, $date->day);

                    while ($slotStart < $slotEnd) {

                        $currentSlotStart = $slotStart->copy();
                        $currentSlotEnd   = $slotStart->copy()->addMinutes($event->duration);

                        // Stop if slot exceeds schedule range
                        if ($currentSlotEnd > $slotEnd) {
                            break;
                        }

                        // Check if slot already booked
                        $isBooked = false;
                        foreach ($bookedSlots as $booked) {
                            if ($currentSlotStart < $booked['end'] && $currentSlotEnd > $booked['start']) {
                                $isBooked = true;
                                break;
                            }
                        }

                        // Store only slot start time (flat array)
                        if (!$isBooked) {
                            $slots[] = $currentSlotStart->toDateTimeString();
                        }

                        // Move to next slot
                        $slotStart->addMinutes($event->duration);
                    }
                }
            }
        }

        return $this->success(

            $slots,
            'Available slots fetched successfully',
            200
        );
    }



    /**
     * Book a slot for an event
     */
    public function bookSlot(Request $request)
    {
        $request->validate([
            'event_id'   => 'required|exists:meeting_events,id',
            'slot_start' => 'required|date',
            'slot_end'   => 'required|date',
            'user_id'    => 'nullable|exists:users,id',
        ]);

        $event = MeetingEvent::findOrFail($request->event_id);

        $slotStart = Carbon::parse($request->slot_start);
        $slotEnd   = Carbon::parse($request->slot_end);

        // Check if slot is already booked
        $isBooked = MeetingBookings::where('meeting_event_id', $event->id)
            ->whereDate('date', $slotStart->toDateString())
            ->whereTime('slot_start', '<', $slotEnd->format('H:i:s'))
            ->whereTime('slot_end', '>', $slotStart->format('H:i:s'))
            ->exists();

        if ($isBooked) {
            return response()->json(['message' => 'Slot already booked!'], 409);
        }

        // Create booking
        $booking = $event->bookings()->create([
            'date'       => $slotStart->toDateString(),
            'slot_start' => $slotStart->toTimeString(),
            'slot_end'   => $slotEnd->toTimeString(),
            'user_id'    => $request->user_id,
        ]);

        return response()->json([
            'message' => 'Slot booked successfully!',
            'booking' => $booking,
        ]);
    }
}
