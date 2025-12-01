<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\MeetingBooking;
use App\Models\MeetingBookingAvailabilities;
use App\Models\MeetingBookingAvailabilitySlot;
use App\Models\MeetingBookingSchedule;
use App\Models\RoomBookings;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    use ApiResponse;
    // -------------------------------------------------------------
    // GET ALL BOOKINGS
    // -------------------------------------------------------------
    public function index()
    {
        $bookings = MeetingBooking::with([
            'schedule:id,meeting_booking_id,duration,timezone,schedule_mode,future_days,date_from,date_to',
            'schedule.availabilities:id,schedule_id,day,is_available',
            'schedule.availabilities.slots:id,availability_id,start_time,end_time'
        ])
            ->select(
                'id',
                'booking_name',
                'booking_date',
                'booking_color',
                'max_invitees',
                'description',
                'online_link',
                'location',
                'status'
            )
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $bookings
        ]);
    }

    // -------------------------------------------------------------
    // GET SINGLE BOOKING
    // -------------------------------------------------------------
    public function show($id)
    {
        $booking = MeetingBooking::with([
            'schedule:id,meeting_booking_id,duration,timezone,schedule_mode,future_days,date_from,date_to',
            'schedule.availabilities:id,schedule_id,day,is_available',
            'schedule.availabilities.slots:id,availability_id,start_time,end_time'
        ])
            ->select(
                'id',
                'booking_name',
                'booking_date',
                'booking_color',
                'max_invitees',
                'description',
                'online_link',
                'location',
                'status'
            )
            ->find($id);

        if (!$booking) {
            return $this->error('Booking not found', 404);
        }

        return $this->success($booking, 'Booking fetched successfully', 200);
    }

    // -------------------------------------------------------------
    // CREATE BOOKING + SCHEDULE + AVAILABILITY
    // -------------------------------------------------------------
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Basic booking fields
            'booking_name'     => 'required|string',
            'booking_date'     => 'required|date',
            'booking_color'    => 'required|string',
            'online_link'      => 'nullable|string',
            'location'         => 'nullable|string',
            'max_invitees'     => 'required|integer|min:1',
            'description'      => 'nullable|string',

            // Schedule fields
            'duration'         => 'required|integer',
            'timezone'         => 'required|string',
            'schedule_mode'    => 'required|in:future,range',

            // Conditional schedule fields
            'future_days'      => 'required_if:schedule_mode,future|nullable|integer|min:1',
            'date_from'        => 'required_if:schedule_mode,range|nullable|date',
            'date_to'          => 'required_if:schedule_mode,range|nullable|date|after_or_equal:date_from',

            // Availability
            'availabilities' => 'required|array',
            'availabilities.*.day'          => 'required|string',
            'availabilities.*.is_available' => 'required|boolean',
            'availabilities.*.slots'        => 'nullable|array',

            // Slots validation
            'availabilities.*.slots.*.start_time' => 'required_with:availabilities.*.slots|date_format:H:i',
            'availabilities.*.slots.*.end_time'   => 'required_with:availabilities.*.slots|date_format:H:i|after:availabilities.*.slots.*.start_time',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // 1. Create Booking
            $booking = MeetingBooking::create([
                'room_id'       => $request->room_id,
                'company_id'    => $request->company_id,
                'created_by'    => Auth::guard('api')->id(),
                'booking_name'  => $request->booking_name,
                'booking_date'  => $request->booking_date,
                'booking_color' => $request->booking_color,
                'online_link'   => $request->online_link,
                'location'      => $request->location,
                'max_invitees'  => $request->max_invitees,
                'description'   => $request->description,
                'status'        => 'pending',
            ]);

            // 2. Create Schedule
            $schedule = MeetingBookingSchedule::create([
                'meeting_booking_id' => $booking->id,
                'duration'           => $request->duration,
                'timezone'           => $request->timezone,
                'schedule_mode'      => $request->schedule_mode,
                'future_days'        => $request->schedule_mode === 'future' ? $request->future_days : null,
                'date_from'          => $request->schedule_mode === 'range' ? $request->date_from : null,
                'date_to'            => $request->schedule_mode === 'range' ? $request->date_to : null,
            ]);

            // 3. Create Availability + Slots
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
            return $this->success($booking, 'Booking created successfully', 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    // -------------------------------------------------------------
    // UPDATE BOOKING + SCHEDULE + AVAILABILITY + SLOTS
    // -------------------------------------------------------------
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            // Basic booking fields
            'booking_name'     => 'required|string',
            'booking_date'     => 'required|date',
            'booking_color'    => 'required|string',
            'online_link'      => 'nullable|string',
            'location'         => 'nullable|string',
            'max_invitees'     => 'required|integer|min:1',
            'description'      => 'nullable|string',

            // Schedule
            'duration'         => 'required|integer',
            'timezone'         => 'required|string',
            'schedule_mode'    => 'required|in:future,range',

            'future_days'      => 'required_if:schedule_mode,future|nullable|integer|min:1',
            'date_from'        => 'required_if:schedule_mode,range|nullable|date',
            'date_to'          => 'required_if:schedule_mode,range|nullable|date|after_or_equal:date_from',

            // Availability
            'availabilities' => 'required|array',
            'availabilities.*.day'          => 'required|string',
            'availabilities.*.is_available' => 'required|boolean',
            'availabilities.*.slots'        => 'nullable|array',
            'availabilities.*.slots.*.start_time' => 'required_with:availabilities.*.slots|date_format:H:i',
            'availabilities.*.slots.*.end_time'   => 'required_with:availabilities.*.slots|date_format:H:i|after:availabilities.*.slots.*.start_time',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $booking = MeetingBooking::with('schedule.availabilities.slots')->findOrFail($id);

            // ---------------------------------------------
            // 1. Update Booking
            // ---------------------------------------------
            $booking->update([
                'room_id'       => $request->room_id,
                'company_id'    => $request->company_id,
                'booking_name'  => $request->booking_name,
                'booking_date'  => $request->booking_date,
                'booking_color' => $request->booking_color,
                'online_link'   => $request->online_link,
                'location'      => $request->location,
                'max_invitees'  => $request->max_invitees,
                'description'   => $request->description,
                'status'        => $request->status ?? $booking->status,
            ]);

            // ---------------------------------------------
            // 2. Normalize schedule (hasOne / hasMany)
            // ---------------------------------------------
            $scheduleRelation = $booking->relationLoaded('schedule')
                ? $booking->getRelation('schedule')
                : $booking->schedule();

            if ($scheduleRelation instanceof \Illuminate\Database\Eloquent\Collection) {
                $schedule = $scheduleRelation->first();
            } elseif ($scheduleRelation instanceof \Illuminate\Database\Eloquent\Model) {
                $schedule = $scheduleRelation;
            } else {
                $schedule = $booking->schedule()->first();
            }

            if (!$schedule) {
                $schedule = MeetingBookingSchedule::create([
                    'meeting_booking_id' => $booking->id,
                    'duration'           => $request->duration,
                    'timezone'           => $request->timezone,
                    'schedule_mode'      => $request->schedule_mode,
                    'future_days'        => $request->schedule_mode === 'future' ? $request->future_days : null,
                    'date_from'          => $request->schedule_mode === 'range' ? $request->date_from : null,
                    'date_to'            => $request->schedule_mode === 'range' ? $request->date_to : null,
                ]);
            } else {
                // update existing schedule
                $schedule->update([
                    'duration'       => $request->duration,
                    'timezone'       => $request->timezone,
                    'schedule_mode'  => $request->schedule_mode,
                    'future_days'    => $request->schedule_mode === 'future' ? $request->future_days : null,
                    'date_from'      => $request->schedule_mode === 'range' ? $request->date_from : null,
                    'date_to'        => $request->schedule_mode === 'range' ? $request->date_to : null,
                ]);

                // Delete old availability + slots
                $availabilityIds = $schedule->availabilities()->pluck('id')->toArray();

                if (!empty($availabilityIds)) {
                    MeetingBookingAvailabilitySlot::whereIn('availability_id', $availabilityIds)->delete();
                    MeetingBookingAvailabilities::whereIn('id', $availabilityIds)->delete();
                }
            }

            // ---------------------------------------------
            // 3. Insert new availability + slots
            // ---------------------------------------------
            foreach ($request->availabilities as $dayItem) {

                $availability = MeetingBookingAvailabilities::create([
                    'schedule_id'  => $schedule->id,
                    'day'          => $dayItem['day'],
                    'is_available' => $dayItem['is_available'],
                ]);

                if (!empty($dayItem['slots'])) {
                    $slotData = [];
                    foreach ($dayItem['slots'] as $slot) {
                        $slotData[] = [
                            'availability_id' => $availability->id,
                            'start_time'      => $slot['start_time'],
                            'end_time'        => $slot['end_time'],
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ];
                    }
                    MeetingBookingAvailabilitySlot::insert($slotData);
                }
            }

            DB::commit();

            return $this->success(
                $booking->load('schedule.availabilities.slots'),
                "Booking updated successfully",
                200
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    // -------------------------------------------------------------
    // DELETE BOOKING (CASCADE deletes all children)
    // -------------------------------------------------------------
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $booking = MeetingBooking::with('schedule.availabilities.slots')->findOrFail($id);

            // Cascade delete handled by DB (foreign keys)
            $booking->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "Booking and related schedule/availabilities/slots deleted successfully"
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete booking',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
