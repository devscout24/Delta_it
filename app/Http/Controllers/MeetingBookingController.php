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

    public function requestList()
    {
        $bookings = MeetingBookingCreates::with('meetingBooking.room')
            ->where('user_id', Auth::guard('api')->id())
            ->where('status', 'pending')
            ->get();

        // Map the response to match the Flutter model
        $response = $bookings->map(function ($booking) {
            return [
                'id'           => $booking->id,
                'booking_name' => $booking->meetingBooking->booking_name ?? null,
                'date'         => $booking->date,
                'start_time'   => $booking->start_time,
                'end_time'     => $booking->end_time,
                'status'       => $booking->status,
                'room'         => $booking->meetingBooking && $booking->meetingBooking->room ? [
                    'id'   => $booking->meetingBooking->room->id,
                    'name' => $booking->meetingBooking->room->name,
                    'area' => $booking->meetingBooking->room->area ?? null,
                ] : null,
            ];
        });

        return $this->success($response, 'Bookings fetched successfully', 200);
    }

    // Admin: all booking requests (pending)
    public function requestsAll()
    {
        $bookings = MeetingBookingCreates::with('meetingBooking.room', 'user')
            ->where('status', 'pending')
            ->get();

        if ($bookings->isEmpty()) {
            return $this->error([], 'No booking requests found.', 404);
        }

        return $this->success($bookings, 'Booking requests retrieved successfully.', 200);
    }


    public function cancelBooking($id)
    {
        $booking = MeetingBookingCreates::find($id);

        if (!$booking) {
            return $this->error('Booking not found', 200);
        }

        $booking->status = 'cancelled';
        $booking->save();

        return $this->success($booking, 'Booking cancelled successfully', 200);
    }
}
