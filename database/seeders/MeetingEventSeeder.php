<?php

namespace Database\Seeders;

use App\Models\MeetingEvent;
use App\Models\MeetingEventSchedule;
use App\Models\MeetingEventScheduleDay;
use App\Models\MeetingEventSlot;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MeetingEventSeeder extends Seeder
{
    public function run()
    {
        // Virtual Events
        $virtualEvents = [
            [
                'title' => 'Weekly Tech Standup',
                'type' => 'virtual',
                'meeting_link' => 'https://zoom.us/j/1234567890',
                'duration' => 30,
                'max_invitees' => 20,
                'description' => 'Weekly team sync-up meeting for tech discussions',
                'color' => '#4A90E2',
            ],
            [
                'title' => 'Product Roadmap Review',
                'type' => 'virtual',
                'meeting_link' => 'https://meet.google.com/abc-defg-hij',
                'duration' => 60,
                'max_invitees' => 15,
                'description' => 'Quarterly product roadmap and feature planning',
                'color' => '#7ED321',
            ],
            [
                'title' => 'Investor Webinar',
                'type' => 'virtual',
                'meeting_link' => 'https://webinar.platform.com/investors',
                'duration' => 90,
                'max_invitees' => 100,
                'description' => 'Monthly webinar for investors and stakeholders',
                'color' => '#F5A623',
            ],
            [
                'title' => 'Training Session - Laravel',
                'type' => 'virtual',
                'meeting_link' => 'https://training.platform.com/laravel-101',
                'duration' => 120,
                'max_invitees' => 50,
                'description' => 'Comprehensive Laravel framework training session',
                'color' => '#FF6B6B',
            ],
        ];

        // Physical Events
        $physicalEvents = [
            [
                'title' => 'Networking Breakfast',
                'type' => 'physical',
                'location' => 'Main Conference Room - Floor 2',
                'duration' => 90,
                'max_invitees' => 30,
                'description' => 'Monthly networking breakfast for all team members',
                'color' => '#BD10E0',
            ],
            [
                'title' => 'Team Building Workshop',
                'type' => 'physical',
                'location' => 'Workshop Area - Floor 1',
                'duration' => 180,
                'max_invitees' => 25,
                'description' => 'Interactive team building and collaboration workshop',
                'color' => '#50E3C2',
            ],
            [
                'title' => 'Client Presentation',
                'type' => 'physical',
                'location' => 'Executive Board Room - Floor 3',
                'duration' => 60,
                'max_invitees' => 20,
                'description' => 'Client project presentation and feedback session',
                'color' => '#B8E986',
            ],
            [
                'title' => 'Partner Summit',
                'type' => 'physical',
                'location' => 'Grand Hall - Floor 4',
                'duration' => 240,
                'max_invitees' => 80,
                'description' => 'Annual partner summit with keynote speeches',
                'color' => '#FF4081',
            ],
        ];

        // Create Virtual Events
        foreach ($virtualEvents as $event) {
            $createdEvent = MeetingEvent::create($event);
            $this->createEventSchedules($createdEvent);
        }

        // Create Physical Events
        foreach ($physicalEvents as $event) {
            $createdEvent = MeetingEvent::create($event);
            $this->createEventSchedules($createdEvent);
        }
    }

    private function createEventSchedules($event)
    {
        // Create 3 schedules for different months
        $startDates = [
            Carbon::now()->startOfMonth(),
            Carbon::now()->addMonth()->startOfMonth(),
            Carbon::now()->addMonths(2)->startOfMonth(),
        ];

        foreach ($startDates as $startDate) {
            $schedule = MeetingEventSchedule::create([
                'meeting_event_id' => $event->id,
                'date' => $startDate,
            ]);

            // Create schedule days (recurring days of week)
            $this->createScheduleDays($schedule, $event);
        }
    }

    private function createScheduleDays($schedule, $event)
    {
        $daysAndTimes = [
            ['day' => 'Monday', 'start' => '09:00:00', 'end' => '17:00:00'],
            ['day' => 'Tuesday', 'start' => '10:00:00', 'end' => '18:00:00'],
            ['day' => 'Wednesday', 'start' => '09:30:00', 'end' => '17:30:00'],
            ['day' => 'Thursday', 'start' => '10:00:00', 'end' => '18:00:00'],
            ['day' => 'Friday', 'start' => '09:00:00', 'end' => '17:00:00'],
        ];

        // Randomly pick 2-3 days for this schedule
        $selectedDays = array_slice($daysAndTimes, rand(0, 2), rand(2, 3));

        foreach ($selectedDays as $dayInfo) {
            $scheduleDay = MeetingEventScheduleDay::create([
                'schedule_id' => $schedule->id,
                'day_of_week' => $dayInfo['day'],
                'start_time' => $dayInfo['start'],
                'end_time' => $dayInfo['end'],
            ]);

            // Create time slots for this schedule day
            $this->createEventSlots($schedule, $dayInfo['start'], $dayInfo['end'], $event->duration);
        }
    }

    private function createEventSlots($schedule, $startTime, $endTime, $duration)
    {
        // Parse times
        $start = Carbon::createFromFormat('H:i:s', $startTime);
        $end = Carbon::createFromFormat('H:i:s', $endTime);

        // Create slots based on event duration
        while ($start->copy()->addMinutes($duration)->lessThanOrEqualTo($end)) {
            MeetingEventSlot::create([
                'meeting_event_schedule_id' => $schedule->id,
                'start_time' => $start->format('H:i:s'),
                'end_time' => $start->copy()->addMinutes($duration)->format('H:i:s'),
                'is_booked' => rand(0, 1) === 1, // Randomly book some slots
            ]);

            $start->addMinutes($duration + 15); // 15 min break between slots
        }
    }
}
