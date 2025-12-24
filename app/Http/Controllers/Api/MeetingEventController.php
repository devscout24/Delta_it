<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MeetingEvent;
use App\Models\MeetingEventAvailabilities;
use App\Models\MeetingEventAvailabilitySlot;
use App\Models\MeetingEventSchedule;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MeetingEventController extends Controller
{
    use ApiResponse;
    // ---------------------------------------------
    // GET ALL EVENTS
    // ---------------------------------------------
    public function index()
    {
        $events = MeetingEvent::with([
            'schedule:id,meeting_event_id,duration,timezone,schedule_mode,future_days,date_from,date_to',
            'schedule.availabilities:id,schedule_id,day,is_available',
            'schedule.availabilities.slots:id,availability_id,start_time,end_time'
        ])
            ->select('id', 'event_name', 'event_date', 'event_color', 'max_invitees', 'description', 'online_link', 'location', 'status')
            ->orderBy('id', 'desc')
            ->get();

        return $this->success($events, 'Events fetched successfully', 200);
    }
    // ---------------------------------------------
    // GET SINGLE EVENT
    // ---------------------------------------------
    public function show($id)
    {
        $event = MeetingEvent::with([
            'schedule:id,meeting_event_id,duration,timezone,schedule_mode,future_days,date_from,date_to',
            'schedule.availabilities:id,schedule_id,day,is_available',
            'schedule.availabilities.slots:id,availability_id,start_time,end_time'
        ])
            ->select('id', 'event_name', 'event_date', 'event_color', 'max_invitees', 'description', 'online_link', 'location', 'status')
            ->find($id);

        if (!$event) {
            return $this->error('Event not found', 404);
        }

        return $this->success($event, 'Event fetched successfully', 200);
    }
    // ---------------------------------------------
    // CREATE EVENT + SCHEDULE + AVAILABILITY
    // ---------------------------------------------
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Basic event fields
            'event_name'     => 'required|string',
            'event_date'     => 'required|date',
            'event_color'    => 'required|string',
            'online_link'    => 'nullable|string',
            'location'       => 'nullable|string',
            'max_invitees'   => 'required|integer|min:1',
            'description'    => 'required|string',

            // Schedule fields
            'duration'       => 'required|integer',
            'timezone'       => 'required|string',
            'schedule_mode'  => 'required|in:future,range',

            // Conditional schedule fields
            'future_days'    => 'required_if:schedule_mode,future|nullable|integer|min:1',
            'date_from'      => 'required_if:schedule_mode,range|nullable|date',
            'date_to'        => 'required_if:schedule_mode,range|nullable|date|after_or_equal:date_from',

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

            // 1. Create Event
            $event = MeetingEvent::create([
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
            // 2. Create Schedule
            $schedule = MeetingEventSchedule::create([
                'meeting_event_id' => $event->id,
                'duration'         => $request->duration,
                'timezone'         => $request->timezone,
                'schedule_mode'    => $request->schedule_mode,
                'future_days'      => $request->schedule_mode === 'future' ? $request->future_days : null,
                'date_from'        => $request->schedule_mode === 'range' ? $request->date_from : null,
                'date_to'          => $request->schedule_mode === 'range' ? $request->date_to : null,
            ]);

            // 3. Create Availability + Slots
            foreach ($request->availabilities as $dayItem) {

                $availability = MeetingEventAvailabilities::create([
                    'schedule_id'  => $schedule->id,
                    'day'          => $dayItem['day'],
                    'is_available' => $dayItem['is_available'],
                ]);

                if (!empty($dayItem['slots'])) {
                    foreach ($dayItem['slots'] as $slot) {
                        MeetingEventAvailabilitySlot::create([
                            'availability_id' => $availability->id,
                            'start_time'      => $slot['start_time'],
                            'end_time'        => $slot['end_time'],
                        ]);
                    }
                }
            }

            DB::commit();

            return $this->success($event, 'Event created successfully', 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    // ---------------------------------------------
    // UPDATE EVENT + SCHEDULE + AVAILABILITY + SLOTS
    // ---------------------------------------------
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            // (same validation rules as before)
            'event_name'     => 'required|string',
            'event_date'     => 'required|date',
            'event_color'    => 'required|string',
            'online_link'    => 'nullable|string',
            'location'       => 'nullable|string',
            'max_invitees'   => 'required|integer|min:1',
            'description'    => 'required|string',

            'duration'       => 'required|integer',
            'timezone'       => 'required|string',
            'schedule_mode'  => 'required|in:future,range',

            'future_days'    => 'required_if:schedule_mode,future|nullable|integer|min:1',
            'date_from'      => 'required_if:schedule_mode,range|nullable|date',
            'date_to'        => 'required_if:schedule_mode,range|nullable|date|after_or_equal:date_from',

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
            $event = MeetingEvent::with('schedule.availabilities.slots')->findOrFail($id);

            // 1. Update event
            $event->update([
                'room_id'       => $request->room_id,
                'company_id'    => $request->company_id,
                'event_name'    => $request->event_name,
                'event_date'    => $request->event_date,
                'event_color'   => $request->event_color,
                'online_link'   => $request->online_link,
                'max_invitees'  => $request->max_invitees,
                'description'   => $request->description,
                'status'        => $request->status ?? $event->status,
            ]);

            // 2. Normalize schedule: support both hasOne or hasMany relationships
            $scheduleRelation = $event->relationLoaded('schedule') ? $event->getRelation('schedule') : $event->schedule();

            // If schedule is a Collection (hasMany), pick the first schedule
            if ($scheduleRelation instanceof \Illuminate\Database\Eloquent\Collection) {
                $schedule = $scheduleRelation->first();
            } elseif ($scheduleRelation instanceof \Illuminate\Database\Eloquent\Model) {
                $schedule = $scheduleRelation;
            } else {
                // If relation method returned a Relation instance, fetch first()
                $schedule = $event->schedule()->first();
            }

            // If no schedule exists, create one
            if (! $schedule) {
                $schedule = MeetingEventSchedule::create([
                    'meeting_event_id' => $event->id,
                    'duration'         => $request->duration,
                    'timezone'         => $request->timezone,
                    'schedule_mode'    => $request->schedule_mode,
                    'future_days'      => $request->schedule_mode === 'future' ? $request->future_days : null,
                    'date_from'        => $request->schedule_mode === 'range' ? $request->date_from : null,
                    'date_to'          => $request->schedule_mode === 'range' ? $request->date_to : null,
                ]);
            } else {
                // 3. Update schedule model
                $schedule->update([
                    'duration'       => $request->duration,
                    'timezone'       => $request->timezone,
                    'schedule_mode'  => $request->schedule_mode,
                    'future_days'    => $request->schedule_mode === 'future' ? $request->future_days : null,
                    'date_from'      => $request->schedule_mode === 'range' ? $request->date_from : null,
                    'date_to'        => $request->schedule_mode === 'range' ? $request->date_to : null,
                ]);

                // 4. Remove old availability + slots (bulk deletes)
                $availabilityIds = $schedule->availabilities()->pluck('id')->toArray();

                if (!empty($availabilityIds)) {
                    \App\Models\MeetingEventAvailabilitySlot::whereIn('availability_id', $availabilityIds)->delete();
                    \App\Models\MeetingEventAvailabilities::whereIn('id', $availabilityIds)->delete();
                }
            }

            // 5. Insert new availability & slots
            foreach ($request->availabilities as $dayItem) {
                $availability = MeetingEventAvailabilities::create([
                    'schedule_id'  => $schedule->id,
                    'day'          => $dayItem['day'],
                    'is_available' => $dayItem['is_available'],
                ]);

                if (!empty($dayItem['slots'])) {
                    $slotsToInsert = [];
                    foreach ($dayItem['slots'] as $slot) {
                        $slotsToInsert[] = [
                            'availability_id' => $availability->id,
                            'start_time'      => $slot['start_time'],
                            'end_time'        => $slot['end_time'],
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ];
                    }
                    if (!empty($slotsToInsert)) {
                        MeetingEventAvailabilitySlot::insert($slotsToInsert);
                    }
                }
            }

            DB::commit();

            return $this->success($event->load('schedule.availabilities.slots'), "Event updated successfully", 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->error($th->getMessage(), 'Something went wrong', 500);
        }
    }


    // ---------------------------------------------
    // DELETE EVENT (CASCADE deletes all schedule+slots)
    // ---------------------------------------------
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $event = MeetingEvent::with('schedule.availabilities.slots')->findOrFail($id);

            // If schedule exists, remove its nested children first
            // if ($event->schedule) {
            //     foreach ($event->schedule->availabilities as $availability) {
            //         // delete slots
            //         $availability->slots()->delete();
            //     }

            //     // delete availabilities
            //     if ($event->schedule->availabilities()->count() > 0) {
            //         $event->schedule->availabilities()->delete();
            //     }

            //     // delete schedule
            //     $event->schedule->delete();
            // }

            // // delete event
            $event->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "Meeting event and its schedule/availabilities/slots deleted successfully"
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete event',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
