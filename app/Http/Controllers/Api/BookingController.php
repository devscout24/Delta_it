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
}
