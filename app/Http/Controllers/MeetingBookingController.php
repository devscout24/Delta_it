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

    // Admin: all booking requests (pending) including booking configurations created via /meeting-bookings/create
    public function requestsAll()
    {
        // pending user booking requests
        $requests = MeetingBookingCreates::with('meetingBooking.room', 'user')
            ->where('status', 'pending')
            ->get()
            ->map(function ($b) {
                return [
                    'id' => $b->id,
                    'type' => 'booking_request',
                    'booking_name' => $b->meetingBooking->booking_name ?? null,
                    'date' => $b->date,
                    'start_time' => $b->start_time,
                    'end_time' => $b->end_time,
                    'status' => $b->status,
                    'room' => $b->meetingBooking && $b->meetingBooking->room ? [
                        'id' => $b->meetingBooking->room->id,
                        'name' => $b->meetingBooking->room->room_name,
                        'area' => $b->meetingBooking->room->area ?? null,
                    ] : null,
                    'user' => [
                        'id' => $b->user->id ?? null,
                        'name' => $b->user->name ?? null,
                    ],
                    'created_at' => $b->created_at,
                ];
            });

        // pending booking configurations created by company users
        $configs = MeetingBooking::where('status', 'pending')
            ->with('room', 'creator')
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'type' => 'booking_config',
                    'booking_name' => $c->booking_name,
                    'date' => $c->booking_date ?? null,
                    'start_time' => null,
                    'end_time' => null,
                    'status' => $c->status,
                    'room' => $c->room ? [
                        'id' => $c->room->id,
                        'name' => $c->room->room_name,
                        'area' => $c->room->area ?? null,
                    ] : null,
                    'user' => [
                        'id' => $c->creator->id ?? null,
                        'name' => $c->creator->name ?? null,
                    ],
                    'created_at' => $c->created_at,
                ];
            });

        $combined = $requests->merge($configs)->sortByDesc('created_at')->values();

        if ($combined->isEmpty()) {
            return $this->error([], 'No booking requests found.', 404);
        }

        return $this->success($combined, 'Booking requests retrieved successfully', 200);
    }

    // Approve a booking request or booking configuration
    public function acceptBooking($id)
    {
        // Try user booking request first
        $bookingReq = MeetingBookingCreates::find($id);
        if ($bookingReq) {
            if (!in_array($bookingReq->status, ['pending', 'requested'])) {
                return $this->error([], "Only requested or pending bookings can be approved.", 422);
            }

            $bookingReq->update(['status' => 'approved']);

            return $this->success($bookingReq, 'Booking request approved successfully', 200);
        }

        // Then try booking configuration created via /meeting-bookings/create
        $bookingConfig = MeetingBooking::find($id);
        if ($bookingConfig) {
            if (!in_array($bookingConfig->status, ['pending', 'requested'])) {
                return $this->error([], "Only requested or pending booking configs can be approved.", 422);
            }

            $bookingConfig->update(['status' => 'approved']);

            return $this->success($bookingConfig, 'Booking configuration approved successfully', 200);
        }

        return $this->error('Booking not found', 404);
    }

    // Reject a booking request or booking configuration
    public function rejectBooking($id)
    {
        $bookingReq = MeetingBookingCreates::find($id);
        if ($bookingReq) {
            if (!in_array($bookingReq->status, ['pending', 'requested'])) {
                return $this->error([], "Only requested or pending bookings can be rejected.", 422);
            }

            $bookingReq->update(['status' => 'rejected']);

            return $this->success($bookingReq, 'Booking request rejected successfully', 200);
        }

        $bookingConfig = MeetingBooking::find($id);
        if ($bookingConfig) {
            if (!in_array($bookingConfig->status, ['pending', 'requested'])) {
                return $this->error([], "Only requested or pending booking configs can be rejected.", 422);
            }

            $bookingConfig->update(['status' => 'rejected']);

            return $this->success($bookingConfig, 'Booking configuration rejected successfully', 200);
        }

        return $this->error('Booking not found', 404);
    }


    // Cancel a booking request or booking configuration
    public function cancelBooking($id)
    {
        $bookingReq = MeetingBookingCreates::find($id);
        if ($bookingReq) {
            if (!in_array($bookingReq->status, ['approved', 'requested', 'pending'])) {
                return $this->error([], "Only approved, requested, or pending bookings can be cancelled.", 422);
            }

            $bookingReq->update(['status' => 'cancelled']);

            return $this->success($bookingReq, 'Booking cancelled successfully', 200);
        }

        $bookingConfig = MeetingBooking::find($id);
        if ($bookingConfig) {
            if (!in_array($bookingConfig->status, ['approved', 'requested', 'pending'])) {
                return $this->error([], "Only approved, requested, or pending booking configs can be cancelled.", 422);
            }

            $bookingConfig->update(['status' => 'cancelled']);

            return $this->success($bookingConfig, 'Booking cancelled successfully', 200);
        }

        return $this->error('Booking not found', 404);
    }
}
