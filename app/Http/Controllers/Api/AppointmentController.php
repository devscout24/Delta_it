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
    // 1️⃣ Validate input
    $validation = validator($request->all(), [
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
        'invitees_select'=> 'required|integer',
    ]);

    if ($validation->fails()) {
        return $this->error('Validation Error.', $validation->errors(), 422);
    }

    try {
        DB::beginTransaction();

        //  Create appointment
        $appointment = Appointment::create([
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


    
}


  // 'invitees_select' => $request->invitees_select, 7
    // 'within_date_range' => $request->date_range,


    
// "meeting_id" => "1"
//   "room_id" => "1"
//   "max_invitees" => "10"
//   "event_color" => "#007bff"
//   "description" => "Weekly project status meeting to discuss updates and blockers."


//   "start_date" => "2025-10-14"
//   "end_date" => "2025-10-14"

//   "duration" => "60"
//   "timezone" => "Asia/Dhaka"


// meetingSlot table
//   "day_id" => "1"
//   "date" => "14-10-25"
//   "start_time" => "10:00:00"
//   "end_time" => "11:00:00"



//   "meeting_id" => "Weekly Project Sync-up"
//   "room_id" => "1"
//   "max_invitees" => null
//   "event_color" => null
//   "description" => null
//   "invitees_select" => null
//   "date_range" => null
//   "duration" => null
//   "timezone" => null

// appointment_id	day_of_week	start_time	end_time	availability_status