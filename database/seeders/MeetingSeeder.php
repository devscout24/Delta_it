<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Meeting;
use Carbon\Carbon;

class MeetingSeeder extends Seeder
{
    public function run()
    {
        $meetings = [
            [
                'name' => 'Weekly Project Sync-up',
                'date' => Carbon::today()->toDateString(),
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
                'location' => 'Conference Room A',
                'add_emails' => 'john@example.com, sarah@example.com',
                'meeting_type' => 'physical',
                'online_link' => null,
            ],
            [
                'name' => 'Client Call',
                'date' => Carbon::today()->addDay()->toDateString(),
                'start_time' => '14:00:00',
                'end_time' => '15:00:00',
                'location' => 'Zoom',
                'add_emails' => 'client@example.com',
                'meeting_type' => 'online',
                'online_link' => 'https://zoom.us/abcd1234',
            ],
            [
                'name' => 'Team Retrospective',
                'date' => Carbon::today()->subDays(2)->toDateString(),
                'start_time' => '09:00:00',
                'end_time' => '10:00:00',
                'location' => 'Conference Room B',
                'add_emails' => 'team@example.com',
                'meeting_type' => 'physical',
                'online_link' => null,
            ],
            [
                'name' => 'Design Review',
                'date' => Carbon::today()->addDays(3)->toDateString(),
                'start_time' => '11:00:00',
                'end_time' => '12:00:00',
                'location' => 'Figma',
                'add_emails' => 'designer@example.com',
                'meeting_type' => 'online',
                'online_link' => 'https://figma.com/meeting123',
            ],
            [
                'name' => 'Management Meeting',
                'date' => Carbon::today()->addDays(7)->toDateString(),
                'start_time' => '16:00:00',
                'end_time' => '17:00:00',
                'location' => 'Conference Room C',
                'add_emails' => 'manager@example.com',
                'meeting_type' => 'physical',
                'online_link' => null,
            ],
            [
                'name' => 'Sprint Planning',
                'date' => Carbon::today()->addWeek()->toDateString(),
                'start_time' => '10:00:00',
                'end_time' => '11:30:00',
                'location' => 'Teams',
                'add_emails' => 'team@example.com',
                'meeting_type' => 'online',
                'online_link' => 'https://teams.microsoft.com/meeting123',
            ],
            [
                'name' => 'Marketing Brainstorm',
                'date' => Carbon::today()->subWeek()->toDateString(),
                'start_time' => '13:00:00',
                'end_time' => '14:30:00',
                'location' => 'Conference Room D',
                'add_emails' => 'marketing@example.com',
                'meeting_type' => 'physical',
                'online_link' => null,
            ],
            [
                'name' => 'Product Demo',
                'date' => Carbon::today()->addDays(10)->toDateString(),
                'start_time' => '15:00:00',
                'end_time' => '16:00:00',
                'location' => 'Zoom',
                'add_emails' => 'client@example.com',
                'meeting_type' => 'online',
                'online_link' => 'https://zoom.us/demo123',
            ],
            [
                'name' => 'HR Interview',
                'date' => Carbon::today()->addDays(5)->toDateString(),
                'start_time' => '09:30:00',
                'end_time' => '10:30:00',
                'location' => 'Conference Room E',
                'add_emails' => 'hr@example.com',
                'meeting_type' => 'physical',
                'online_link' => null,
            ],
            [
                'name' => 'Board Meeting',
                'date' => Carbon::today()->addMonth()->toDateString(),
                'start_time' => '11:00:00',
                'end_time' => '13:00:00',
                'location' => 'Conference Room A',
                'add_emails' => 'board@example.com',
                'meeting_type' => 'physical',
                'online_link' => null,
            ],
        ];

        foreach ($meetings as $meeting) {
            Meeting::create($meeting);
        }
    }
}
