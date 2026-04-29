<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TicketMessage;
use App\Models\Ticket;

class TicketMessageSeeder extends Seeder
{
    public function run()
    {
        $tickets = Ticket::all();

        foreach ($tickets as $ticket) {

            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $ticket->user_id,
                'message' => 'Hello, I need help with this issue.',
            ]);

            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => 1, // admin reply
                'message' => 'We are looking into it.',
            ]);
        }
    }
}
