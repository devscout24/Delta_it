<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\RoomBookings;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    // Get Booking Room

    public function index()
    {
        $bookingRooms = Appointment::with('room', 'meeting')->latest()->get();

        $bookingRooms = $bookingRooms->map(function ($booking) {
            return [
                'id' => $booking->id,
                'date'
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $bookingRooms,
            'message' => 'Booking rooms retrieved successfully'
        ], 200);
    }
    // Book Room

    public function bookRoom(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);

        $booking = RoomBookings::create([
            'room_id' => $request->room_id,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'booked_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $booking,
            'message' => 'Room booked successfully'
        ], 201);
    }
}
