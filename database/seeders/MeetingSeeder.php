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
            // ✅ WEEK MEETING (this week)
            [
                'meeting_name' => 'Weekly Standup Meeting',
                'date' => '2025-11-07',   // This week
                'start_time' => '09:00:00',
                'end_time' => '10:00:00',
                'room_id' => 1,
                'meeting_type' => 'online',
                'online_link' => 'https://zoom.us/j/987654321',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ✅ MONTH MEETING (within same month)
            [
                'meeting_name' => 'Monthly Review & Planning',
                'date' => '2025-11-25',   // Same month but different week
                'start_time' => '15:00:00',
                'end_time' => '16:30:00',
                'room_id' => 2,
                'meeting_type' => 'physical',
                'online_link' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ✅ CUSTOM DATE MEETING (future random date)
            [
                'meeting_name' => 'Investor Presentation',
                'date' => '2026-02-10',   // Completely different date
                'start_time' => '11:00:00',
                'end_time' => '13:00:00',
                'room_id' => 3,
                'meeting_type' => 'online',
                'online_link' => 'https://meet.google.com/abc-xyz-def',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
