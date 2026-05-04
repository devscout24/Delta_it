<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\MeetingEvent;
use App\Models\MeetingEventSlot;
use App\Models\MeetingBooking;

class MeetingController extends Controller
{
    use ApiResponse;

    // ======================
    // EVENTS LIST
    // ======================
    public function events()
    {
        $events = MeetingEvent::latest()->get()->map(function ($e) {
            return [
                'id' => $e->id,
                'title' => $e->title,
                'duration' => $e->duration,
                'type' => $e->type,
            ];
        });

        return $this->success($events, 'Events');
    }

    // ======================
    // EVENT DETAILS
    // ======================
    public function eventDetails($id)
    {
        $e = MeetingEvent::find($id);

        if (!$e) return $this->error([], 'Not found', 404);

        return $this->success([
            'id' => $e->id,
            'title' => $e->title,
            'description' => $e->description,
            'duration' => $e->duration,
            'max_invitees' => $e->max_invitees,
            'type' => $e->type,
            'location' => $e->location,
            'meeting_link' => $e->meeting_link,
        ], 'Details');
    }

    // ======================
    // AVAILABLE DATES (NEW)
    // ======================
    public function availableDates($id)
    {
        $dates = MeetingEventSlot::where('event_id', $id)
            ->where('is_booked', false)
            ->pluck('date')
            ->unique()
            ->values();

        return $this->success($dates, 'Available dates');
    }

    // ======================
    // SLOTS BY DATE
    // ======================
    public function slots(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $slots = MeetingEventSlot::where('event_id', $id)
            ->where('date', $request->date)
            ->orderBy('start_time')
            ->get()
            ->map(function ($s) {
                return [
                    'start_time' => $s->start_time,
                    'end_time' => $s->end_time,
                    'is_booked' => $s->is_booked,
                ];
            });

        return $this->success($slots, 'Slots');
    }

    // ======================
    // BOOK REQUEST
    // ======================
    public function book(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event_id'   => 'required|exists:meeting_events,id',
            'date'       => 'required|date',
            'start_time' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $user = Auth::guard('api')->user();

        // check slot exists
        $slot = MeetingEventSlot::where([
            'event_id' => $request->event_id,
            'date' => $request->date,
            'start_time' => $request->start_time
        ])->first();

        if (!$slot) {
            return $this->error([], 'Invalid slot', 404);
        }

        if ($slot->is_booked) {
            return $this->error([], 'Already booked', 422);
        }

        // prevent duplicate request
        $exists = MeetingBooking::where([
            'event_id' => $request->event_id,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'status' => 'pending'
        ])->exists();

        if ($exists) {
            return $this->error([], 'Already requested', 422);
        }

        $booking = MeetingBooking::create([
            'event_id' => $request->event_id,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $slot->end_time,
            'name' => $user->name,
            'email' => $user->email,
            'status' => 'pending'
        ]);

        return $this->success($booking, 'Request sent (Pending approval)');
    }

    // ======================
    // MY MEETINGS (VIRTUAL)
    // ======================
    public function myMeetings()
    {
        $user = Auth::guard('api')->user();

        $data = MeetingBooking::with('event')
            ->where('email', $user->email)
            ->whereHas('event', fn($q) => $q->where('type', 'virtual'))
            ->where('status', 'approved')
            ->get();

        return $this->success($data, 'Virtual meetings');
    }

    // ======================
    // MY BOOKINGS (PHYSICAL)
    // ======================
    public function myBookings()
    {
        $user = Auth::guard('api')->user();

        $data = MeetingBooking::with('event')
            ->where('email', $user->email)
            ->whereHas('event', fn($q) => $q->where('type', 'physical'))
            ->get();

        return $this->success($data, 'Bookings');
    }
}
