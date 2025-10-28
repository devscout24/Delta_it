<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Email;
use App\Models\Meeting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MeetingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        DB::table('meetings')->insert([
            [
                'meeting_name' => 'Weekly Team Sync',
                'date' => '2025-11-05',
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
                'room_id' => 1,
                'add_emails' => json_encode(['john@example.com', 'jane@example.com']),
                'meeting_type' => 'online',
                'online_link' => 'https://zoom.us/j/123456789',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'meeting_name' => 'Project Kickoff',
                'date' => '2025-11-07',
                'start_time' => '14:00:00',
                'end_time' => '15:30:00',
                'room_id' => 2,
                'add_emails' => json_encode(['manager@example.com', 'teamlead@example.com']),
                'meeting_type' => 'offline',
                'online_link' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
