<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;
use App\Models\Floor;

class RoomSeeder extends Seeder
{
    public function run()
    {
        $floors = Floor::all();

        foreach ($floors as $floor) {

            // 3 rooms per floor
            Room::create([
                'floor_id' => $floor->id,
                'name' => 'Room A - Floor ' . $floor->level,
                'area' => 35.5,
                'polygon_points' => json_encode([[0, 0], [5, 0], [5, 7], [0, 7]]),
                'status' => 'available',
            ]);

            Room::create([
                'floor_id' => $floor->id,
                'name' => 'Room B - Floor ' . $floor->level,
                'area' => 20.0,
                'polygon_points' => json_encode([[0, 0], [4, 0], [4, 5], [0, 5]]),
                'status' => 'available',
            ]);

            Room::create([
                'floor_id' => $floor->id,
                'name' => 'Meeting Room - Floor ' . $floor->level,
                'area' => 25.0,
                'polygon_points' => json_encode([[0, 0], [6, 0], [6, 6], [0, 6]]),
                'status' => 'available',
            ]);
        }
    }
}
