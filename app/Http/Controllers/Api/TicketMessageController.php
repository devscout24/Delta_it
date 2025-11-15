<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TicketMessage;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TicketMessageController extends Controller
{
    use ApiResponse;
    public function index($ticket_id)
    {
        if(!TicketMessage::where('ticket_id', $ticket_id)->exists()) {
            return $this->error([], "No messages found", 200);
        }

        $messages = TicketMessage::where('ticket_id', $ticket_id)
            ->with('sender')
            ->orderBy('created_at')
            ->get();

        return $this->success($messages, "Messages fetched");
    }

    public function store(Request $request, $ticket_id)
    {
        $validated = Validator::make($request->all(), [
            'message' => 'nullable|string',
            'type' => 'required|in:text,emoji,link,attachment'
        ]);

        if ($validated->fails()) {
            return $this->error($validated->errors(), 'Validation error', 422);
        }

        $user = Auth::guard('api')->user();

        if(!$user) {
            return $this->error([], "User not found", 404);
        }
        // store validated input
        $message = TicketMessage::create([
            'ticket_id' => $ticket_id,
            'sender_id' => $user->id,
            'message_text' => $request->message,
            'type' => $request->type,
        ]);

        return $this->success($message, "Message sent", 200);
    }
}
