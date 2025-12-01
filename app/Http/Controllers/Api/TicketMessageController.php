<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
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
        $ticket = Ticket::with([
            'assignedToUser:id,name',
            'requester:id,name,email',
        ])->find($ticket_id);

        if (!$ticket) {
            return $this->error([], "Ticket not found", 404);
        }

        $messages = TicketMessage::where('ticket_id', $ticket_id)
            ->with([
                'sender:id,name,email,user_type,profile_photo',
                'files:id,ticket_message_id,file_path,file_type,file_size'
            ])
            ->orderBy('created_at')
            ->get()
            ->map(function ($msg) {

                return [
                    "id" => $msg->id,
                    "ticket_id" => $msg->ticket_id,
                    "message_type" => $msg->message_type,
                    "message_text" => $msg->message_text,
                    "is_read" => $msg->is_read,
                    "created_at" => $msg->created_at->toDateTimeString(),

                    "attachment" => [
                        "path" => asset($msg->attachment_path),
                        "type" => $msg->attachment_type,
                        "size" => $msg->attachment_size,
                    ],

                    "files" => $msg->files->map(function ($file) {
                        return [
                            "path" => asset($file->file_path),
                            "type" => $file->file_type,
                            "size" => $file->file_size,
                        ];
                    }),

                    "sender" => [
                        "id" => $msg->sender->id,
                        "name" => $msg->sender->name,
                        "email" => $msg->sender->email,
                        "user_type" => $msg->sender->user_type,
                        "profile_photo" => $msg->sender->profile_photo,
                    ]
                ];
            });

        return $this->success([
            "ticket" => [        // extra info but doesn't break old model
                "id" => $ticket->id,
                "unique_id" => $ticket->unique_id,
                "subject" => $ticket->subject,
                "type" => $ticket->type,
                "status" => $ticket->status,

                "assigned_to" => $ticket->assignedToUser ? [
                    "id" => $ticket->assignedToUser->id,
                    "name" => $ticket->assignedToUser->name,
                ] : null,

                "requester" => [
                    "id" => $ticket->requester->id,
                    "name" => $ticket->requester->name,
                    "email" => $ticket->requester->email,
                    "role" => $ticket->requester_role,
                ]
            ],

            "messages" => $messages
        ], "Messages fetched successfully");
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

        if (!$user) {
            return $this->error([], "User not found", 404);
        }
        // store validated input
        $message = TicketMessage::create([
            'ticket_id' => $ticket_id,
            'sender_id' => $user->id,
            'message_text' => $request->message,
            'message_type' => $request->type,
        ]);

        return $this->success($message, "Message sent", 200);
    }
}
