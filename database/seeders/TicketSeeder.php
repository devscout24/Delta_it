<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Company;
use App\Models\Room;
use Illuminate\Support\Str;

class TicketSeeder extends Seeder
{
    public function run()
    {
        $companies = Company::where('status', 'active')->get();
        $rooms = Room::all();

        foreach ($companies as $company) {
            $user = User::where('company_id', $company->id)->first();

            if (!$user) continue;

            // Ticket 1: Maintenance issue (Open)
            Ticket::create([
                'unique_id' => 'TIC-' . strtoupper(Str::random(8)),
                'company_id' => $company->id,
                'user_id' => $user->id,
                'requester_id' => $user->id,
                'requester_role' => 'user',
                'room_id' => $rooms->random()->id ?? null,
                'subject' => 'Air conditioning not working in meeting room',
                'type' => 'maintenance',
                'status' => 'pending',
                'date' => now()->toDateString(),
                'action' => 'created',
            ]);

            // Ticket 2: Access issue (In Progress)
            Ticket::create([
                'unique_id' => 'TIC-' . strtoupper(Str::random(8)),
                'company_id' => $company->id,
                'user_id' => $user->id,
                'requester_id' => $user->id,
                'requester_role' => 'user',
                'room_id' => null,
                'subject' => 'Need access card for new employee',
                'type' => 'access',
                'status' => 'in-progress',
                'date' => now()->subDay()->toDateString(),
                'action' => 'updated',
            ]);

            // Ticket 3: Support issue (Solved)
            Ticket::create([
                'unique_id' => 'TIC-' . strtoupper(Str::random(8)),
                'company_id' => $company->id,
                'user_id' => $user->id,
                'requester_id' => $user->id,
                'requester_role' => 'user',
                'room_id' => $rooms->random()->id ?? null,
                'subject' => 'WiFi connection issues',
                'type' => 'support',
                'status' => 'solved',
                'date' => now()->subDays(2)->toDateString(),
                'action' => 'resolved',
            ]);

            // Ticket 4: Other issue (Unsolved)
            Ticket::create([
                'unique_id' => 'TIC-' . strtoupper(Str::random(8)),
                'company_id' => $company->id,
                'user_id' => $user->id,
                'requester_id' => $user->id,
                'requester_role' => 'user',
                'room_id' => null,
                'subject' => 'Printer not responding',
                'type' => 'other',
                'status' => 'unsolved',
                'date' => now()->subHours(5)->toDateString(),
                'action' => 'created',
            ]);
        }
    }
}
