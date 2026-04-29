<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\MeetingRoom;
use App\Models\MeetingRoomSchedule;
use App\Models\MeetingRoomSlot;
use App\Models\MeetingRoomBooking;

class MeetingRoomController extends Controller
{
    use ApiResponse;

    // ======================
    // LIST ROOMS
    // ======================
    public function rooms()
    {
        $rooms = MeetingRoom::latest()->get()->map(function ($r) {
            return [
                'id' => $r->id,
                'name' => $r->name,
                'duration' => $r->duration,
                'color' => $r->color,
            ];
        });

        return $this->success($rooms, 'Rooms fetched');
    }

    // ======================
    // ROOM DETAILS
    // ======================
    public function details($id)
    {
        $room = MeetingRoom::find($id);

        if (!$room) {
            return $this->error([], 'Room not found', 404);
        }

        return $this->success([
            'id' => $room->id,
            'name' => $room->name,
            'location' => $room->name,
            'max_invitees' => $room->capacity,
            'description' => $room->description,
        ], 'Room details');
    }

    // ======================
    // SLOTS
    // ======================
    public function slots(Request $request, $id)
    {
        $request->validate([
            'date' => 'required|date'
        ]);

        $schedule = MeetingRoomSchedule::where('meeting_room_id', $id)
            ->where('date', $request->date)
            ->first();

        if (!$schedule) {
            return $this->success([], 'No slots available');
        }

        $slots = MeetingRoomSlot::where('meeting_room_schedule_id', $schedule->id)
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
    // BOOK
    // ======================
    public function book(Request $request)
    {
        $request->validate([
            'meeting_room_id' => 'required|exists:meeting_rooms,id',
            'slot_id' => 'required|exists:meeting_room_slots,id',
        ]);

        $user = Auth::guard('api')->user();

        if (!$user || !$user->company_id) {
            return $this->error([], 'Unauthorized', 401);
        }

        $slot = MeetingRoomSlot::find($request->slot_id);

        if ($slot->is_booked) {
            return $this->error([], 'Slot already booked', 422);
        }

        $booking = MeetingRoomBooking::create([
            'meeting_room_id' => $request->meeting_room_id,
            'meeting_room_slot_id' => $slot->id,
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $slot->update(['is_booked' => true]);

        return $this->success($booking, 'Booking request submitted');
    }

    // ======================
    // MY BOOKINGS
    // ======================
    public function myBookings()
    {
        $user = Auth::guard('api')->user();

        $bookings = MeetingRoomBooking::with(['room', 'slot'])
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(function ($b) {
                return [
                    'id' => $b->id,
                    'room_name' => $b->room->name,
                    'date' => $b->slot->created_at->format('d M'),
                    'start_time' => $b->slot->start_time,
                    'end_time' => $b->slot->end_time,
                    'status' => $b->status,
                ];
            });

        return $this->success($bookings, 'Bookings fetched');
    }
}
