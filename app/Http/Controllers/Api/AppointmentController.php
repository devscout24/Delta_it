<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\MeetingSlot;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function addAppointment(Request $request)
    {
        // dd($request->all());

        $appointment = Appointment::create([
            'meeting_id' => 2,
            'room_id' => 1,
            'max_invitees' => $request->max_invitees,
            'event_color' => $request->event_color,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'duration' => $request->duration,
            'timezone' => $request->timezone,
        ]);

        // dd($request->invitees_select);

        if ($request->invitees_select) {
            for ($i = 1; $i <= $request->invitees_select; $i++) {
                MeetingSlot::create([
                    'day_id' => $request->day_id,
                    'date' => $request->date,
                    'start_time' => $request->start_time,
                    'end_time' => $request->end_time,
                    'meeting_id' => 2,
                ]);
            }
        }

        // if ($request->invitees_select) {
        //     foreach ($request->slots as $slot) {
        //         MeetingSlot::create([
        //             'day_id' => $slot['day_id'],
        //             'date' => $slot['date'],
        //             'start_time' => $slot['start_time'],
        //             'end_time' => $slot['end_time'],
        //             'meeting_id' => 2,
        //         ]);
        //     }
        // }

        return response()->json(['success' => true]);
    }


    // 'invitees_select' => $request->invitees_select, 7
    // 'within_date_range' => $request->date_range,

}

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