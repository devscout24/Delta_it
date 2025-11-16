<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\MeetingBooking;
use App\Models\MeetingBookingAvailabilities;
use App\Models\MeetingBookingAvailabilitySlot;
use App\Models\MeetingBookingSchedule;
use App\Models\RoomBookings;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    // -------------------------------------------------
    // GET ALL BOOKINGS
    // -------------------------------------------------
    public function index()
    {
        $bookings = MeetingBooking::with([
            'schedule.availabilities.slots'
        ])->orderBy('id', 'desc')->get();

        return response()->json([
            'status' => true,
            'data' => $bookings
        ]);
    }

    // -------------------------------------------------
    // GET SINGLE BOOKING
    // -------------------------------------------------
    public function show($id)
    {
        $booking = MeetingBooking::with([
            'schedule.availabilities.slots'
        ])->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $booking
        ]);
    }

    // -------------------------------------------------
    // CREATE BOOKING + SCHEDULE + AVAILABILITY
    // -------------------------------------------------
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id'       => 'required',
            'company_id'    => 'required',
            'event_name'    => 'required',
            'event_date'    => 'required',
            'event_color'   => 'required',
            'online_link'   => 'nullable',
            'max_invitees'  => 'required',
            'description'   => 'required',
            'duration'      => 'required',
            'timezone'      => 'required',
            'schedule_mode' => 'required',
            'future_days'   => 'required',
            'start_time'    => 'required',
            'end_time'      => 'required',
            'availabilities' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // 1. Create booking
            $booking = MeetingBooking::create([
                'room_id'       => $request->room_id,
                'company_id'    => $request->company_id,
                'created_by'    => Auth::guard('api')->id(),
                'event_name'    => $request->event_name,
                'event_date'    => $request->event_date,
                'event_color'   => $request->event_color,
                'online_link'   => $request->online_link,
                'max_invitees'  => $request->max_invitees,
                'description'   => $request->description,
                'status'        => 'pending',
            ]);

            // 2. Create schedule
            $schedule = MeetingBookingSchedule::create([
                'booking_id'     => $booking->id,
                'duration'       => $request->duration,
                'timezone'       => $request->timezone,
                'schedule_mode'  => $request->schedule_mode,
                'future_days'    => $request->future_days,
                'date_from'      => $request->date_from,
                'date_to'        => $request->date_to,
            ]);

            // 3. Create availability for each day
            foreach ($request->availabilities as $dayItem) {
                $availability = MeetingBookingAvailabilities::create([
                    'schedule_id'  => $schedule->id,
                    'day'          => $dayItem['day'],
                    'is_available' => $dayItem['is_available'],
                ]);

                // Add slots only if available
                if (!empty($dayItem['slots'])) {
                    foreach ($dayItem['slots'] as $slot) {
                        MeetingBookingAvailabilitySlot::create([
                            'availability_id' => $availability->id,
                            'start_time'      => $slot['start_time'],
                            'end_time'        => $slot['end_time'],
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "Booking created successfully",
                'data' => $booking->load('schedule.availabilities.slots')
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['status' => false, 'error' => $th->getMessage()], 500);
        }
    }

    // -------------------------------------------------
    // UPDATE BOOKING
    // -------------------------------------------------
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $booking = MeetingBooking::findOrFail($id);

            // Update booking info
            $booking->update([
                'room_id'       => $request->room_id,
                'company_id'    => $request->company_id,
                'event_name'    => $request->event_name,
                'event_date'    => $request->event_date,
                'event_color'   => $request->event_color,
                'online_link'   => $request->online_link,
                'max_invitees'  => $request->max_invitees,
                'description'   => $request->description,
                'status'        => $request->status ?? $booking->status,
            ]);

            // Update schedule
            $schedule = $booking->schedule;

            $schedule->update([
                'duration'       => $request->duration,
                'timezone'       => $request->timezone,
                'schedule_mode'  => $request->schedule_mode,
                'future_days'    => $request->future_days,
                'date_from'      => $request->date_from,
                'date_to'        => $request->date_to,
            ]);

            // Remove old availability + slots
            foreach ($schedule->availabilities as $availability) {
                $availability->slots()->delete();
            }

            $schedule->availabilities()->delete();

            // Insert new availability
            foreach ($request->availabilities as $dayItem) {

                $availability = MeetingBookingAvailabilities::create([
                    'schedule_id'  => $schedule->id,
                    'day'          => $dayItem['day'],
                    'is_available' => $dayItem['is_available'],
                ]);

                if (!empty($dayItem['slots'])) {
                    foreach ($dayItem['slots'] as $slot) {
                        MeetingBookingAvailabilitySlot::create([
                            'availability_id' => $availability->id,
                            'start_time'      => $slot['start_time'],
                            'end_time'        => $slot['end_time'],
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "Booking updated successfully",
                'data' => $booking->load('schedule.availabilities.slots')
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // -------------------------------------------------
    // DELETE BOOKING
    // -------------------------------------------------
    public function destroy($id)
    {
        $booking = MeetingBooking::findOrFail($id);
        $booking->delete();

        return response()->json([
            'status' => true,
            'message' => "Booking deleted successfully"
        ]);
    }
}
