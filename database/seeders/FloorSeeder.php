<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Floor;

class FloorSeeder extends Seeder
{
    public function run()
    {
        $floors = [
            ['name' => 'Floor -1', 'level' => 0],
            ['name' => 'Floor 0',  'level' => 1],
            ['name' => 'Floor 1',  'level' => 2],
            ['name' => 'Floor 2',  'level' => 3],
        ];

        foreach ($floors as $floor) {
            Floor::create($floor);
        }
    }
}
