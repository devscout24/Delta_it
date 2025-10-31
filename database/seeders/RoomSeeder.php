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
                'room_name'      => 'Main Conference Room',
                'area'           => 50.00, // numeric value
                'polygon_points' => json_encode([[0, 0], [0, 5], [5, 5], [5, 0]]), // example points
                'company_id'     => 1,
                'status'         => 'available',
            ],
            [
                'room_name'      => 'Project Discussion Room',
                'area'           => 35.00,
                'polygon_points' => json_encode([[0, 0], [0, 4], [4, 4], [4, 0]]),
                'company_id'     => 1,
                'status'         => 'available',
            ],
            [
                'room_name'      => 'Client Meeting Room',
                'area'           => 40.00,
                'polygon_points' => json_encode([[0, 0], [0, 6], [6, 6], [6, 0]]),
                'company_id'     => 2,
                'status'         => 'available',
            ],
            [
                'room_name'      => 'Training Hall',
                'area'           => 80.00,
                'polygon_points' => json_encode([[0, 0], [0, 10], [10, 10], [10, 0]]),
                'company_id'     => 3,
                'status'         => 'available',
            ],
            [
                'room_name'      => 'Board Room',
                'area'           => 60.00,
                'polygon_points' => json_encode([[0, 0], [0, 8], [8, 8], [8, 0]]),
                'company_id'     => 1,
                'status'         => 'available',
            ],
        ]);
    }
}
