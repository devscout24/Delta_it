<?php

namespace App\Http\Controllers;

use App\Models\MeetingBooking;
use App\Models\MeetingBookingCreates;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MeetingBookingController extends Controller
{
    use ApiResponse;
    public function list()
    {
        $bookings = MeetingBooking::with('room')
            ->select('id', 'room_id', 'booking_name', 'max_invitees', 'description')
            ->get();

        return $this->success($bookings, 'Bookings fetched successfully', 200);
    }
    public function details($id)
    {
        $booking = MeetingBooking::with([
            'room',
            'schedules.availabilitySlots',
            'schedules.availabilitySlots.timeRanges'
        ])->findOrFail($id);

        return $this->success($booking, 'Booking details fetched successfully', 200);
    }
    public function createBooking(Request $request)
    {
        $request->validate([
            'meeting_booking_id' => 'required|exists:meeting_bookings,id',
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'invitees' => 'required|integer|min:1',
        ]);

        // Optional: Check availability logic here...

        $booking = MeetingBookingCreates::create([
            'user_id' => Auth::guard('api')->user()->id,
            'meeting_booking_id' => $request->meeting_booking_id,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'invitees' => $request->invitees,
            'status' => 'pending',
        ]);

        return $this->success($booking, 'Booking created successfully', 201);
    }
}
