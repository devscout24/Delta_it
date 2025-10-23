<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Meeting;
use App\Models\Email;
use Carbon\Carbon;

class MeetingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $meetings = [
            [
                'meeting_name' => 'Project Kickoff',
                'date' => '2025-10-15',
                'start_time' => '09:00:00',
                'end_time' => '10:00:00',
                'room_id' => 1,
                'meeting_type' => 'physical',
                'online_link' => null,
                'add_emails' => ['john@example.com', 'sarah@example.com', 'david@example.com'],
            ],
            [
                'meeting_name' => 'Weekly Team Sync',
                'date' => '2025-10-13',
                'start_time' => '11:00:00',
                'end_time' => '12:00:00',
                'room_id' => 2,
                'meeting_type' => 'online',
                'online_link' => 'https://meet.google.com/abc-xyz',
                'add_emails' => ['team1@example.com', 'team2@example.com'],
            ],
            [
                'meeting_name' => 'Client Presentation',
                'date' => '2025-10-20',
                'start_time' => '15:00:00',
                'end_time' => '16:30:00',
                'room_id' => 3,
                'meeting_type' => 'physical',
                'online_link' => null,
                'add_emails' => ['client@example.com', 'manager@example.com'],
            ],
            [
                'meeting_name' => 'Monthly Planning',
                'date' => '2025-11-01',
                'start_time' => '10:00:00',
                'end_time' => '12:00:00',
                'room_id' => 4,
                'meeting_type' => 'physical',
                'online_link' => null,
                'add_emails' => ['lead@example.com', 'planner@example.com'],
            ],
        ];

        foreach ($meetings as $data) {
            $meeting = Meeting::create([
                'meeting_name' => $data['meeting_name'],
                'date' => $data['date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'room_id' => $data['room_id'],
                'meeting_type' => $data['meeting_type'],
                'online_link' => $data['online_link'], // if stored as comma-separated
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // Add related emails
            foreach ($data['add_emails'] as $email) {
                Email::create([
                    'meeting_id' => $meeting->id,
                    'email' => $email,
                ]);
            }
        }
    }
}
