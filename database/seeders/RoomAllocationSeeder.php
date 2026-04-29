<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RoomAllocation;
use App\Models\Room;
use App\Models\Company;

class RoomAllocationSeeder extends Seeder
{
    public function run()
    {
        $rooms = Room::all();
        $companies = Company::where('status', 'active')->get();

        foreach ($rooms as $index => $room) {

            // Assign only if company exists
            if (isset($companies[$index])) {

                RoomAllocation::create([
                    'room_id' => $room->id,
                    'company_id' => $companies[$index]->id,
                    'start_date' => now()->subMonths(rand(1, 6)),
                    'end_date' => null,
                    'status' => 'active',
                ]);

                // Update room status (optional but useful)
                $room->update([
                    'status' => 'occupied'
                ]);
            }
        }
    }
}
