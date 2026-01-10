<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\MeetingBooking;
use App\Models\MeetingBookingAvailabilities;
use App\Models\MeetingBookingAvailabilitySlot;
use App\Models\MeetingBookingSchedule;
use App\Models\RoomBookings;
use App\Models\MeetingBookingCreates;
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
        $bookings = MeetingBooking::with('schedules')->select(
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

        $bookings = $bookings->map(function ($booking) {
            return [
                'id'            => $booking->id,
                'booking_name'  => $booking->booking_name,
                'booking_date'  => $booking->booking_date,
                'booking_color' => $booking->booking_color,
                'max_invitees'  => $booking->max_invitees,
                'description'   => $booking->description,
                'online_link'   => $booking->online_link,
                'location'      => $booking->location,
                'status'        => $booking->status,
                'average_duration' => round(
                    $booking->schedules->avg('duration'),
                    2
                ),
            ];
        });


        return $this->success($bookings, 'Bookings fetched successfully', 200);
    }

    // -------------------------------------------------------------
    // GET SINGLE BOOKING
    // -------------------------------------------------------------
    public function show($id)
    {
        $booking = MeetingBooking::select(
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

        // Normalize and validate day values to match DB enum (mon, tue, wed, thu, fri, sat, sun)
        $availabilities = $request->input('availabilities', []);
        $allowedDays = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
        foreach ($availabilities as $idx => $dayItem) {
            $day = $dayItem['day'] ?? null;
            $normalized = $this->normalizeDay($day);
            if (!$normalized || !in_array($normalized, $allowedDays)) {
                return response()->json([
                    'status' => false,
                    'errors' => ['availabilities.' . $idx . '.day' => ['Invalid day: ' . ($day ?? 'null')]]
                ], 422);
            }
            $availabilities[$idx]['day'] = $normalized;
        }
        // persist normalized availabilities back into the request
        $request->merge(['availabilities' => $availabilities]);

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
            return $this->error($validator->errors(), $validator->errors()->first(), 200);
        }

        // Normalize and validate day values to match DB enum (mon, tue, wed, thu, fri, sat, sun)
        $availabilities = $request->input('availabilities', []);
        $allowedDays = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
        $errors = [];
        foreach ($availabilities as $idx => $item) {
            $day = $item['day'] ?? null;
            $normalized = $this->normalizeDay($day);
            if (!$normalized || !in_array($normalized, $allowedDays)) {
                $errors['availabilities.' . $idx . '.day'] = 'Invalid day: ' . ($day ?? 'null');
            } else {
                $availabilities[$idx]['day'] = $normalized;
            }
        }
        if (!empty($errors)) {
            return $this->error($errors, 'Invalid availability day', 200);
        }
        $request->merge(['availabilities' => $availabilities]);

        DB::beginTransaction();

        try {
            $booking = MeetingBooking::with('schedule.availabilities.slots')->find($id);

            if (!$booking) {
                return $this->error('Booking not found', 404);
            }

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
            foreach ($request->availabilities as $item) {
                $availability = MeetingBookingAvailabilities::create([
                    'schedule_id' => $schedule->id, // REQUIRED
                    'day'         => $item['day'],
                    'is_available' => $item['is_available'],
                ]);

                if (!empty($item['slots'])) {
                    foreach ($item['slots'] as $slot) {
                        MeetingBookingAvailabilitySlot::create([
                            'availability_id' => $availability->id,
                            'start_time'      => $slot['start_time'],
                            'end_time'        => $slot['end_time'],
                        ]);
                    }
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
            $booking = MeetingBooking::with('schedule.availabilities.slots')->find($id);

            if (!$booking) {
                return $this->error([], 'Booking not found', 404);
            }

            // Cascade delete handled by DB (foreign keys)
            $booking->delete();

            DB::commit();

            return $this->success([], 'Booking deleted successfully', 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    // -------------------------------------------------------------
    // GET REQUEST LIST (only user booking requests)
    // -------------------------------------------------------------
    public function requestList()
    {
        $requests = MeetingBookingCreates::with('meetingBooking.room', 'user')
            ->where('status', 'pending')
            ->get();

        if ($requests->isEmpty()) {
            return $this->success([], 'Request list is empty', 200);
        }

        $response = $requests->map(function ($b) {
            return [
                'id' => $b->id,
                'meeting_booking_id' => $b->meeting_booking_id,
                'booking_name' => $b->meetingBooking->booking_name ?? null,
                'date' => $b->date,
                'start_time' => $b->start_time,
                'end_time' => $b->end_time,
                'invitees' => $b->invitees,
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

        return $this->success($response, 'Request list', 200);
    }

    // -------------------------------------------------------------
    // SHOW BOOKING DETAILS WITH AVAILABILITIES AND TIME SLOTS
    // For mobile users to view booking details before creating a request
    // Returns: title, location, max invites, description, available dates and time slots
    // -------------------------------------------------------------
    public function showRequestDetails($id)
    {
        $booking = MeetingBooking::with([
            'schedules.availabilities.slots' => function ($query) {
                $query->orderBy('start_time', 'asc');
            }
        ])->find($id);

        if (!$booking) {
            return $this->error('Booking not found', 404);
        }

        // Format the response with relevant details
        $response = [
            'id' => $booking->id,
            'title' => $booking->booking_name,
            'location' => $booking->location,
            'max_invites' => $booking->max_invitees,
            'description' => $booking->description,
            'online_link' => $booking->online_link,
            'booking_color' => $booking->booking_color,
            'availabilities' => []
        ];

        // Process availabilities and time slots from all schedules
        if ($booking->schedules && count($booking->schedules) > 0) {
            foreach ($booking->schedules as $schedule) {
                if ($schedule->availabilities && count($schedule->availabilities) > 0) {
                    foreach ($schedule->availabilities as $availability) {
                        if ($availability->is_available) {
                            $availabilityData = [
                                'id' => $availability->id,
                                'day' => $availability->day,
                                'slots' => []
                            ];

                            // Format time slots
                            if ($availability->slots && count($availability->slots) > 0) {
                                foreach ($availability->slots as $slot) {
                                    $availabilityData['slots'][] = [
                                        'id' => $slot->id,
                                        'start_time' => $slot->start_time,
                                        'end_time' => $slot->end_time,
                                    ];
                                }
                            }

                            $response['availabilities'][] = $availabilityData;
                        }
                    }
                }
            }
        }

        return $this->success($response, 'Booking details fetched successfully', 200);
    }

    // -------------------------------------------------------------
    // CREATE A USER BOOKING REQUEST (mobile users book a booking config)
    // -------------------------------------------------------------
    public function createBookingRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'meeting_booking_id' => 'required|exists:meeting_bookings,id',
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'invitees' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $meeting = MeetingBooking::find($request->meeting_booking_id);
        if (!$meeting) {
            return $this->error('Booking not found', 404);
        }

        // Optional: ensure invitees do not exceed max_invitees
        if (!empty($meeting->max_invitees) && $request->invitees > $meeting->max_invitees) {
            return $this->error(['invitees' => 'Exceeds maximum invitees for this booking'], 'Validation error', 422);
        }

        $userId = Auth::guard('api')->id();
        if (!$userId) {
            return $this->error('Unauthenticated', 401);
        }

        $req = MeetingBookingCreates::create([
            'user_id' => $userId,
            'meeting_booking_id' => $request->meeting_booking_id,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'invitees' => $request->invitees,
            'status' => 'pending',
        ]);

        return $this->success($req, 'Booking request created successfully', 201);
    }

    // -------------------------------------------------------------
    // GET current authenticated user's booking requests
    // -------------------------------------------------------------
    public function myRequests()
    {
        $userId = Auth::guard('api')->id();
        $requests = MeetingBookingCreates::with('meetingBooking.room')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();

        if ($requests->isEmpty()) {
            return $this->success([], 'No booking requests found for the user', 200);
        }

        $response = $requests->map(function ($b) {
            return [
                'id' => $b->id,
                'meeting_booking_id' => $b->meeting_booking_id,
                'booking_name' => $b->meetingBooking->booking_name ?? null,
                'date' => $b->date,
                'start_time' => $b->start_time,
                'end_time' => $b->end_time,
                'invitees' => $b->invitees,
                'status' => $b->status,
                'room' => $b->meetingBooking && $b->meetingBooking->room ? [
                    'id' => $b->meetingBooking->room->id,
                    'name' => $b->meetingBooking->room->room_name,
                ] : null,
                'created_at' => $b->created_at,
            ];
        });

        return $this->success($response, 'User booking requests fetched', 200);
    }

    // -------------------------------------------------------------
    // APPROVE / REJECT / CANCEL BOOKING (handles both booking configs and user requests)
    // -------------------------------------------------------------
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

        $booking = MeetingBooking::find($id);

        if (!$booking) {
            return $this->error([], 'Booking not found', 404);
        }

        if (!in_array($booking->status, ['requested', 'pending'])) {
            return $this->error([], "Only requested or pending bookings can be approved.", 422);
        }

        $booking->update(['status' => 'approved']);

        return $this->success($booking, 'Booking request approved successfully', 200);
    }

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

        $booking = MeetingBooking::find($id);

        if (!$booking) {
            return $this->error([], 'Booking not found', 404);
        }

        if (!in_array($booking->status, ['requested', 'pending'])) {
            return $this->error([], "Only requested or pending bookings can be rejected.", 422);
        }

        $booking->update(['status' => 'rejected']);

        return $this->success($booking, 'Booking request rejected successfully', 200);
    }

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

        $booking = MeetingBooking::find($id);

        if (!$booking) {
            return $this->error([], 'Booking not found', 404);
        }

        if (!in_array($booking->status, ['approved', 'requested', 'pending'])) {
            return $this->error([], "Only approved, requested, or pending bookings can be cancelled.", 422);
        }

        $booking->update(['status' => 'cancelled']);

        return $this->success($booking, 'Booking cancelled successfully', 200);
    }


    /**
     * Normalize day to DB enum short form (mon, tue, ...)
     */
    private function normalizeDay(?string $day): ?string
    {
        if (empty($day)) {
            return null;
        }

        $d = strtolower(trim($day));

        $map = [
            'monday' => 'mon',
            'mon' => 'mon',
            'tuesday' => 'tue',
            'tue' => 'tue',
            'tues' => 'tue',
            'wednesday' => 'wed',
            'wed' => 'wed',
            'thursday' => 'thu',
            'thu' => 'thu',
            'thur' => 'thu',
            'thurs' => 'thu',
            'friday' => 'fri',
            'fri' => 'fri',
            'saturday' => 'sat',
            'sat' => 'sat',
            'sunday' => 'sun',
            'sun' => 'sun',
        ];

        if (isset($map[$d])) {
            return $map[$d];
        }

        $short = substr($d, 0, 3);

        return $map[$short] ?? null;
    }
}
