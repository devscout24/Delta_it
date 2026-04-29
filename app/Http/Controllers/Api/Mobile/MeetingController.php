<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\MeetingEvent;
use App\Models\MeetingEventSchedule;
use App\Models\MeetingEventSlot;
use App\Models\MeetingBooking;

class MeetingController extends Controller
{
    use ApiResponse;

    // ======================
    // LIST EVENTS
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

        return $this->success($events, 'Events fetched');
    }

    // ======================
    // EVENT DETAILS
    // ======================
    public function eventDetails($id)
    {
        $event = MeetingEvent::find($id);

        if (!$event) {
            return $this->error([], 'Event not found', 404);
        }

        return $this->success([
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'duration' => $event->duration,
            'max_invitees' => $event->max_invitees,
            'type' => $event->type,
            'location' => $event->location,
            'meeting_link' => $event->meeting_link,
        ], 'Event details');
    }

    // ======================
    // GET SLOTS
    // ======================
    public function slots(Request $request, $id)
    {
        $request->validate([
            'date' => 'required|date'
        ]);

        $schedule = MeetingEventSchedule::where('meeting_event_id', $id)
            ->where('date', $request->date)
            ->first();

        if (!$schedule) {
            return $this->success([], 'No slots available');
        }

        $slots = MeetingEventSlot::where('meeting_event_schedule_id', $schedule->id)
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'start_time' => $s->start_time,
                    'end_time' => $s->end_time,
                    'is_booked' => $s->is_booked,
                ];
            });

        return $this->success($slots, 'Slots fetched');
    }

    // ======================
    // BOOK MEETING
    // ======================
    public function book(Request $request)
    {
        $request->validate([
            'meeting_event_id' => 'required|exists:meeting_events,id',
            'slot_id' => 'required|exists:meeting_event_slots,id',
        ]);

        $user = Auth::guard('api')->user();

        if (!$user || !$user->company_id) {
            return $this->error([], 'Unauthorized', 401);
        }

        $slot = MeetingEventSlot::find($request->slot_id);

        if ($slot->is_booked) {
            return $this->error([], 'Slot already booked', 422);
        }

        $booking = MeetingBooking::create([
            'meeting_event_id' => $request->meeting_event_id,
            'meeting_event_slot_id' => $slot->id,
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        // mark slot as booked
        $slot->update(['is_booked' => true]);

        return $this->success($booking, 'Meeting request submitted');
    }

    // ======================
    // MY MEETINGS
    // ======================
    public function myMeetings()
    {
        $user = Auth::guard('api')->user();

        $meetings = MeetingBooking::with(['event', 'slot'])
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'title' => $m->event->title,
                    'date' => $m->slot->created_at->format('d M'),
                    'start_time' => $m->slot->start_time,
                    'end_time' => $m->slot->end_time,
                    'status' => $m->status,
                    'meeting_link' => $m->event->meeting_link,
                ];
            });

        return $this->success($meetings, 'My meetings fetched');
    }
}
