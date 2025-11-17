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
    public function index()
    {
        $bookings = MeetingBooking::with('room')
            ->select('id', 'room_id', 'booking_name', 'max_invitees', 'description')
            ->get();

        return $this->success($bookings, 'Bookings fetched successfully', 200);
    }
    public function details($id)
    {
        $booking = MeetingBooking::with([
            'room:id,room_name',
            'schedules:id,meeting_booking_id,duration,schedule_mode,future_days,date_from,date_to',
            'schedules.availabilitySlots:id,schedule_id,day,is_available',
            'schedules.availabilitySlots.timeRanges:id,availability_id,start_time,end_time',
        ])->findOrFail($id);

        $formatted = [
            'id' => $booking->id,
            'booking_name' => $booking->booking_name,
            'room' => [
                'id' => $booking->room->id,
                'name' => $booking->room->room_name,
            ],
            'max_invitees' => $booking->max_invitees,
            'description' => $booking->description,
            'color' => $booking->booking_color,
            'online_link' => $booking->online_link,

            'schedule' => $booking->schedules->map(function ($schedule) {
                return [
                    'duration' => $schedule->duration,
                    'schedule_mode' => $schedule->schedule_mode,
                    'future_days' => $schedule->future_days,
                    'date_from' => $schedule->date_from,
                    'date_to' => $schedule->date_to,

                    'availability' => $schedule->availabilitySlots->map(function ($slot) {
                        return [
                            'day' => $slot->day,
                            'is_available' => $slot->is_available,
                            'time_ranges' => $slot->timeRanges->map(function ($tr) {
                                return [
                                    'start_time' => $tr->start_time,
                                    'end_time' => $tr->end_time
                                ];
                            })
                        ];
                    })
                ];
            })
        ];

        return $this->success($formatted, 'Booking details fetched successfully', 200);
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
