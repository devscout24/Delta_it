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
            // First message from requester
            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $ticket->requester_id ?? $ticket->user_id,
                'sender_id' => $ticket->requester_id ?? $ticket->user_id,
                'message' => 'Hello, I need help with this issue.',
                'message_type' => 'text',
                'message_text' => 'Hello, I need help with this issue.',
                'is_read' => true,
            ]);

            // Reply from admin (user ID 1)
            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => 1,
                'sender_id' => 1,
                'message' => 'We are looking into it. Please wait for updates.',
                'message_type' => 'text',
                'message_text' => 'We are looking into it. Please wait for updates.',
                'is_read' => false,
            ]);

            // Additional message based on ticket status
            if ($ticket->status === 'solved') {
                TicketMessage::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => 1,
                    'sender_id' => 1,
                    'message' => 'Issue has been resolved. Please let us know if you need further assistance.',
                    'message_type' => 'text',
                    'message_text' => 'Issue has been resolved. Please let us know if you need further assistance.',
                    'is_read' => true,
                ]);
            }
        }
    }
}
