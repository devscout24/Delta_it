<?php

namespace App\Services;

class SlotService
{

    function splitTimeSlots($start_time, $end_time, $duration_minutes)
    {
        $slots = [];
        $start = strtotime($start_time);
        $end   = strtotime($end_time);

        while ($start < $end) {
            $slot_start = date('H:i:s', $start);
            $slot_end   = date('H:i:s', strtotime("+$duration_minutes minutes", $start));

            if (strtotime($slot_end) > $end) break;

            $slots[] = [
                'start' => $slot_start,
                'end'   => $slot_end,
            ];

            $start = strtotime("+$duration_minutes minutes", $start);
        }

        return $slots;
    }


    public function getAvailableSlots(Request $request)
    {
        $date = Carbon::parse($request->date); // selected date
        $dayName = $date->format('l'); // Sunday, Monday etc.
        $duration = 60; // Meeting duration in minutes (Change if needed)

        // 1) Get availability for that weekday
        $schedules = WeeklySchedule::where('day', $dayName)->get();

        // If no schedule found = day unavailable
        if ($schedules->isEmpty()) {
            return response()->json([]);
        }

        $slots = [];

        foreach ($schedules as $schedule) {

            $start = Carbon::parse($schedule->start_time);
            $end = Carbon::parse($schedule->end_time);

            // 2) Loop to generate slot blocks
            while ($start->copy()->addMinutes($duration)->lte($end)) {

                $slotStart = $start->format('H:i');
                $slotEnd = $start->copy()->addMinutes($duration)->format('H:i');

                // 3) Check if already booked
                $isBooked = Appointment::where('date', $date->toDateString())
                    ->where('start_time', $slotStart)
                    ->exists();

                if (!$isBooked) {
                    $slots[] = [
                        'start' => $slotStart,
                        'end' => $slotEnd,
                    ];
                }

                $start->addMinutes($duration);
            }
        }

        return response()->json($slots);
    }
}
