<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Company;
use App\Models\Room;

class TicketSeeder extends Seeder
{
    public function run()
    {
        $companies = Company::where('status', 'active')->get();
        $rooms = Room::all();

        foreach ($companies as $company) {

            $user = User::where('company_id', $company->id)->first();

            if (!$user) continue;

            Ticket::create([
                'company_id' => $company->id,
                'user_id' => $user->id,
                'room_id' => $rooms->random()->id ?? null,
                'subject' => 'Air conditioning issue',
                'type' => 'maintenance',
                'status' => 'open',
            ]);

            Ticket::create([
                'company_id' => $company->id,
                'user_id' => $user->id,
                'room_id' => null,
                'subject' => 'Need access card',
                'type' => 'access',
                'status' => 'in_progress',
            ]);
        }
    }
}
