<?php

namespace Database\Seeders;

use App\Models\Day;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $day = [
            ['day' => 'Sunday'],
            ['day' => 'Monday'],
            ['day' => 'Tuesday'],
            ['day' => 'Wednesday'],
            ['day' => 'Thursday'],
            ['day' => 'Friday'],
            ['day' => 'Saturday'],
        ];

        foreach ($day as $days) {
            Day::create($days);
        }
    }
}
