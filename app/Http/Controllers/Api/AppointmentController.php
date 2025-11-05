<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Services\SlotService;
use Carbon\Carbon;
use App\Models\Appointment;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\WeeklySchedule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\AppointmentSlot;

class AppointmentController extends Controller
{

    use ApiResponse;

    protected $slotService;
    public function __construct(SlotService $slotService)
    {
        $this->slotService = $slotService;
    }

    public function addAppointment(Request $request)
    {
        //  Validate input
        $validation = validator($request->all(), [
            'event_name'     => 'required',
            'meeting_id'     => 'required',
            'room_id'        => 'required',
            'max_invitees'   => 'required',
            'event_color'    => 'required',
            'description'    => 'required',
            'duration'       => 'required|integer',
            'timezone'       => 'required',
            'day'            => 'required|array',
            'start_time'     => 'required|array',
            'end_time'       => 'required|array',
            'invitees_select' => 'required|integer',
        ]);

        if ($validation->fails()) {
            return $this->error('Validation Error.', $validation->errors(), 422);
        }

        try {
            DB::beginTransaction();

            //  Create appointment
            $appointment = Appointment::create([
                'event_name'   => $request->event_name,
                'meeting_id'   => $request->meeting_id,
                'room_id'      => $request->room_id,
                'max_invitees' => $request->max_invitees,
                'event_color'  => $request->event_color,
                'description'  => $request->description,
                'duration'     => $request->duration,
                'timezone'     => $request->timezone,
            ]);

            //  Create weekly schedules
            $days = count($request->day);
            for ($i = 0; $i < $days; $i++) {
                WeeklySchedule::create([
                    'appointment_id' => $appointment->id,
                    'meeting_id'     => $request->meeting_id,
                    'day'            => $request->day[$i],
                    'start_time'     => $request->start_time[$i],
                    'end_time'       => $request->end_time[$i],
                ]);
            }

            //  Get date range for slot generation
            $startDate = Carbon::today();
            $endDate   = Carbon::today()->addDays((int)$request->invitees_select);

            //  Fetch all weekly schedules for this meeting
            $weeklySchedules = WeeklySchedule::where('meeting_id', $request->meeting_id)->get();

            //  Loop through each day schedule and generate slots
            foreach ($weeklySchedules as $weekly) {
                $slots = $this->slotService->splitTimeSlots(
                    $weekly->start_time,
                    $weekly->end_time,
                    (int)$request->duration
                );

                foreach ($slots as $slot) {
                    AppointmentSlot::create([
                        'appointment_id'      => $appointment->id,
                        'weekly_schedule_id'  => $weekly->id,
                        'meeting_id'          => $request->meeting_id,
                        'day'                 => $weekly->day,
                        'start_time'          => $slot['start'],
                        'end_time'            => $slot['end'],
                        'availability_status' => 'available',
                    ]);
                }
            }

            DB::commit();

            return $this->success('Meeting Scheduled & Slots Generated Successfully', [
                'appointment' => $appointment,
                'slots' => AppointmentSlot::where('appointment_id', $appointment->id)->get(),
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error('Server Error', $e->getMessage(), 500);
        }
    }


    public function updateAppointment(Request $request, $id)
    {
        // Validate request
        $validated = $request->validate([
            'event_name'       => 'required|string|max:255',
            'meeting_id'       => 'required|integer|exists:meetings,id',
            'room_id'          => 'required|integer|exists:rooms,id',
            'max_invitees'     => 'required|integer|min:1',
            'event_color'      => 'required|string|max:7',
            'description'      => 'required|string',
            'duration'         => 'required|integer|min:1',
            'timezone'         => 'required|string',
            'day'              => 'required|array',
            'day.*'            => 'required|string',
            'start_time'       => 'required|array',
            'start_time.*'     => 'required|date_format:H:i',
            'end_time'         => 'required|array',
            'end_time.*'       => 'required|date_format:H:i|after:start_time.*',
            'invitees_select'  => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            // Find existing appointment
            $appointment = Appointment::findOrFail($id);

            // Update appointment details
            $appointment->update($validated);

            // Remove old schedules and slots
            $oldSchedules = WeeklySchedule::where('appointment_id', $appointment->id)->get();
            foreach ($oldSchedules as $weekly) {
                AppointmentSlot::where('weekly_schedule_id', $weekly->id)->delete();
            }
            WeeklySchedule::where('appointment_id', $appointment->id)->delete();

            // Create new weekly schedules and slots
            collect($validated['day'])->each(function ($day, $index) use ($appointment, $validated) {
                $weekly = WeeklySchedule::create([
                    'appointment_id' => $appointment->id,
                    'meeting_id'     => $validated['meeting_id'],
                    'day'            => $day,
                    'start_time'     => $validated['start_time'][$index],
                    'end_time'       => $validated['end_time'][$index],
                ]);

                // Generate slots
                $slots = $this->slotService->splitTimeSlots(
                    $weekly->start_time,
                    $weekly->end_time,
                    $validated['duration']
                );

                foreach ($slots as $slot) {
                    AppointmentSlot::create([
                        'appointment_id'      => $appointment->id,
                        'weekly_schedule_id'  => $weekly->id,
                        'meeting_id'          => $validated['meeting_id'],
                        'day'                 => $day,
                        'start_time'          => $slot['start'],
                        'end_time'            => $slot['end'],
                        'availability_status' => 'available',
                    ]);
                }
            });

            DB::commit();

            return $this->success('Appointment Updated & Slots Regenerated Successfully', [
                'appointment' => $appointment,
                'slots'       => AppointmentSlot::where('appointment_id', $appointment->id)->get(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Server Error', $e->getMessage(), 500);
        }
    }



    function getAllEvents()
    {
        $events = Appointment::with(['room', 'meeting'])->get();

        if ($events->isEmpty()) {
            return $this->error('Events not found', 404);
        }

        $formattedEvents = $events->map(fn($event) => [
            'id' => $event->id,
            'event_name' => $event->event_name,
            'meeting' => $event->meeting ? [
                'id' => $event->meeting->id,
                'meeting_name' => $event->meeting->meeting_name,
                'date' => $event->meeting->date,
                'duration' => $event->duration,
                'event_color' => $event->event_color,
            ] : null,
            'room' => $event->room ? [
                'id' => $event->room->id,
                'room_name' => $event->room->room_name,
            ] : null,
        ]);

        return $this->success($formattedEvents, 'Events fetched successfully', 200);
    }
}
