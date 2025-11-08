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
}
