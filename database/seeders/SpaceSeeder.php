<?php

namespace Database\Seeders;

use App\Models\Space;
use App\Models\SpaceSchedule;
use App\Models\SpaceScheduleDay;
use App\Models\SpaceSlot;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SpaceSeeder extends Seeder
{
    public function run()
    {
        $spaces = [
            [
                'name' => 'Conference Room A',
                'capacity' => 20,
                'description' => 'Professional conference room with AV equipment, whiteboard, and comfortable seating',
                'color' => '#4A90E2',
                'is_active' => true,
            ],
            [
                'name' => 'Meeting Room B',
                'capacity' => 8,
                'description' => 'Intimate meeting space perfect for one-on-one or small team discussions',
                'color' => '#7ED321',
                'is_active' => true,
            ],
            [
                'name' => 'Collaboration Hub',
                'capacity' => 15,
                'description' => 'Open collaborative space with modern furniture and digital display screens',
                'color' => '#F5A623',
                'is_active' => true,
            ],
            [
                'name' => 'Training Center',
                'capacity' => 40,
                'description' => 'Large training facility with projectors, interactive boards, and classroom setup',
                'color' => '#FF6B6B',
                'is_active' => true,
            ],
            [
                'name' => 'Executive Board Room',
                'capacity' => 25,
                'description' => 'High-end boardroom with video conferencing, premium equipment, and elegant furnishings',
                'color' => '#BD10E0',
                'is_active' => true,
            ],
            [
                'name' => 'Breakout Area',
                'capacity' => 12,
                'description' => 'Casual space for informal meetings, brainstorming, and team gatherings',
                'color' => '#50E3C2',
                'is_active' => true,
            ],
            [
                'name' => 'Innovation Lab',
                'capacity' => 18,
                'description' => 'Specialized workspace for creative projects with modern tech and flexible layout',
                'color' => '#B8E986',
                'is_active' => true,
            ],
            [
                'name' => 'Quiet Zone',
                'capacity' => 5,
                'description' => 'Soundproof individual focus rooms for concentrated work and private calls',
                'color' => '#FF4081',
                'is_active' => true,
            ],
        ];

        foreach ($spaces as $spaceData) {
            $space = Space::create($spaceData);
            $this->createSpaceSchedules($space);
        }
    }

    private function createSpaceSchedules($space)
    {
        // Create schedules for current month and next 2 months
        $schedules = [
            [
                'start_date' => Carbon::now()->startOfMonth(),
                'end_date' => Carbon::now()->endOfMonth(),
            ],
            [
                'start_date' => Carbon::now()->addMonth()->startOfMonth(),
                'end_date' => Carbon::now()->addMonth()->endOfMonth(),
            ],
            [
                'start_date' => Carbon::now()->addMonths(2)->startOfMonth(),
                'end_date' => Carbon::now()->addMonths(2)->endOfMonth(),
            ],
        ];

        foreach ($schedules as $scheduleData) {
            $schedule = SpaceSchedule::create([
                'space_id' => $space->id,
                'start_date' => $scheduleData['start_date'],
                'end_date' => $scheduleData['end_date'],
            ]);

            // Create schedule days
            $this->createScheduleDays($space, $schedule, $scheduleData['start_date'], $scheduleData['end_date']);
        }
    }

    private function createScheduleDays($space, $schedule, $startDate, $endDate)
    {
        $weekDays = [
            ['day' => 'Monday', 'start' => '08:00:00', 'end' => '18:00:00'],
            ['day' => 'Tuesday', 'start' => '08:00:00', 'end' => '18:00:00'],
            ['day' => 'Wednesday', 'start' => '08:00:00', 'end' => '18:00:00'],
            ['day' => 'Thursday', 'start' => '08:00:00', 'end' => '18:00:00'],
            ['day' => 'Friday', 'start' => '08:00:00', 'end' => '18:00:00'],
        ];

        // For training center and conference rooms, extend hours
        if (in_array($space->name, ['Training Center', 'Conference Room A', 'Executive Board Room'])) {
            $weekDays = [
                ['day' => 'Monday', 'start' => '07:00:00', 'end' => '20:00:00'],
                ['day' => 'Tuesday', 'start' => '07:00:00', 'end' => '20:00:00'],
                ['day' => 'Wednesday', 'start' => '07:00:00', 'end' => '20:00:00'],
                ['day' => 'Thursday', 'start' => '07:00:00', 'end' => '20:00:00'],
                ['day' => 'Friday', 'start' => '07:00:00', 'end' => '20:00:00'],
                ['day' => 'Saturday', 'start' => '09:00:00', 'end' => '17:00:00'],
            ];
        }

        // For quiet zone, shorter hours
        if ($space->name === 'Quiet Zone') {
            $weekDays = [
                ['day' => 'Monday', 'start' => '08:00:00', 'end' => '17:00:00'],
                ['day' => 'Tuesday', 'start' => '08:00:00', 'end' => '17:00:00'],
                ['day' => 'Wednesday', 'start' => '08:00:00', 'end' => '17:00:00'],
                ['day' => 'Thursday', 'start' => '08:00:00', 'end' => '17:00:00'],
                ['day' => 'Friday', 'start' => '08:00:00', 'end' => '17:00:00'],
            ];
        }

        foreach ($weekDays as $dayInfo) {
            SpaceScheduleDay::create([
                'schedule_id' => $schedule->id,
                'day_of_week' => $dayInfo['day'],
                'start_time' => $dayInfo['start'],
                'end_time' => $dayInfo['end'],
            ]);

            // Create slots for each day in the schedule period
            $this->createSpaceSlots($space, $schedule, $dayInfo, $startDate, $endDate);
        }
    }

    private function createSpaceSlots($space, $schedule, $dayInfo, $startDate, $endDate)
    {
        $dayName = $dayInfo['day'];
        $startTime = $dayInfo['start'];
        $endTime = $dayInfo['end'];

        // Map day names to Carbon day constants
        $dayMap = [
            'Monday' => 1,
            'Tuesday' => 2,
            'Wednesday' => 3,
            'Thursday' => 4,
            'Friday' => 5,
            'Saturday' => 6,
            'Sunday' => 0,
        ];

        $currentDate = $startDate->copy();

        // Iterate through all dates in the schedule
        while ($currentDate->lessThanOrEqualTo($endDate)) {
            // Check if this date matches the day of week
            if ($currentDate->dayOfWeek == $dayMap[$dayName]) {
                // Create 30-minute slots
                $slotStart = Carbon::createFromFormat('Y-m-d H:i:s', $currentDate->format('Y-m-d') . ' ' . $startTime);
                $slotEnd = Carbon::createFromFormat('Y-m-d H:i:s', $currentDate->format('Y-m-d') . ' ' . $endTime);

                while ($slotStart->copy()->addMinutes(30)->lessThanOrEqualTo($slotEnd)) {
                    SpaceSlot::create([
                        'space_id' => $space->id,
                        'date' => $slotStart->format('Y-m-d'),
                        'start_time' => $slotStart->format('H:i:s'),
                        'end_time' => $slotStart->copy()->addMinutes(30)->format('H:i:s'),
                        'is_booked' => rand(0, 2) === 0, // 33% chance of being booked
                    ]);

                    $slotStart->addMinutes(30);
                }
            }

            $currentDate->addDay();
        }
    }
}
