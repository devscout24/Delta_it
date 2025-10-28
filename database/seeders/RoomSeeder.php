<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        Room::insert([
            [
                'room_name' => 'Main Conference Room',
                'area'      => 'Building A - 1st Floor',
                'position'  => 'Near Reception',
            ],
            [
                'room_name' => 'Project Discussion Room',
                'area'      => 'Building A - 2nd Floor',
                'position'  => 'Next to HR Office',
            ],
            [
                'room_name' => 'Client Meeting Room',
                'area'      => 'Building B - 3rd Floor',
                'position'  => 'Corner Office',
            ],
            [
                'room_name' => 'Training Hall',
                'area'      => 'Building C - Ground Floor',
                'position'  => 'Left Wing',
            ],
            [
                'room_name' => 'Board Room',
                'area'      => 'Head Office - 5th Floor',
                'position'  => 'Central Section',
            ],
        ]);
    }
}
