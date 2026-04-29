<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;

class TicketAttachmentSeeder extends Seeder
{
    public function run()
    {
        $messages = TicketMessage::all();

        foreach ($messages as $message) {

            TicketAttachment::create([
                'ticket_message_id' => $message->id,
                'file_path' => 'tickets/sample.png',
            ]);
        }
    }
}
