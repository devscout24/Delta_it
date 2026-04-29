<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Floor;

class FloorSeeder extends Seeder
{
    public function run()
    {
        Floor::create([
            'name' => 'Ground Floor',
            'level' => 0,
        ]);

        Floor::create([
            'name' => 'First Floor',
            'level' => 1,
        ]);

        Floor::create([
            'name' => 'Second Floor',
            'level' => 2,
        ]);
    }
}
