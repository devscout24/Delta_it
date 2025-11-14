<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TicketMessage;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class TicketMessageController extends Controller
{
    use ApiResponse;
    public function index($ticket_id)
    {
        $messages = TicketMessage::where('ticket_id', $ticket_id)
            ->with('sender')
            ->orderBy('created_at')
            ->get();

        return $this->success($messages, "Messages fetched");
    }

    public function store(Request $request, $ticket_id)
    {
        $validated = $request->validate([
            'message' => 'nullable|string',
            'type' => 'required|in:text,emoji,link,attachment'
        ]);

        $message = TicketMessage::create([
            'ticket_id' => $ticket_id,
            'sender_id' => auth()->id(),
            'message' => $validated['message'],
            'type' => $validated['type'],
        ]);

        return $this->success($message, "Message sent");
    }
}
