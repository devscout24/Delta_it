<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    use ApiResponse;
    public function index(Request $request)
    {
        $tickets = Ticket::with(['company', 'requester'])

            ->when($request->status == 'deleted', function ($q) {
                $q->onlyTrashed();
            })

            ->when($request->company_id, function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            })

            ->when($request->status && $request->status != 'deleted', function ($q) use ($request) {
                $allowed = ['pending', 'in-progress', 'unsolved', 'solved'];
                if (in_array($request->status, $allowed)) {
                    $q->where('status', $request->status);
                }
            })

            ->when($request->company_id, function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            })

            ->orderBy('created_at', 'desc')
            ->get();

        $tickets = $tickets->map(function ($ticket) {
            return [
                'id'           => $ticket->id,
                'unique_id'    => $ticket->unique_id,
                'subject'      => $ticket->subject,
                'type'         => $ticket->type,
                'status'       => $ticket->status,
                'date'         => $ticket->date,
                'action'       => $ticket->action,
                'deleted_at'   => $ticket->deleted_at,

                // Company (only important fields)
                'company' => [
                    'id'   => $ticket->company->id ?? null,
                    'name' => $ticket->company->name ?? null,
                    'logo' => asset($ticket->company->logo) ?? null,
                ],

                // Requester (only useful fields)
                'requester' => [
                    'id'       => $ticket->requester->id ?? null,
                    'name'     => $ticket->requester->name ?? null,
                    'email'    => $ticket->requester->email ?? null,
                    'user_type' => $ticket->requester->user_type ?? null,
                ],
            ];
        });

        return $this->success($tickets, "Tickets fetched successfully");
    }

    public function store(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'subject'       => 'required|string',
            'company_id'    => 'required|exists:companies,id',
            'type'          => 'required|string',
            'room_id'       => 'nullable|exists:rooms,id',
            'message'       => 'required|string',

            // NEW FIELD (Account)
            'assigned_to'   => 'nullable|exists:users,id',
        ]);

        if ($validated->fails()) {
            return $this->error(null, $validated->errors()->first(), 422);
        }

        // requester = logged-in admin/user
        $requesterId = Auth::guard('api')->id();

        $ticket = Ticket::create([
            'unique_id'    => 'TIC-' . strtoupper(Str::random(10)),
            'subject'      => $request->subject,
            'company_id'   => $request->company_id,
            'requester_id' => $requesterId,
            'assigned_to'  => $request->assigned_to ?? null,   // <-- NEW
            'type'         => $request->type,
            'status'       => 'pending',
            'room_id'      => $request->room_id,
            'action'       => 'created',
            'date'         => now()->toDateString(),
        ]);

        // Insert first message
        TicketMessage::create([
            'ticket_id'    => $ticket->id,
            'sender_id'    => $requesterId,
            'message_type' => 'text',
            'message_text' => $request->message,
            'is_read'      => false,
        ]);

        return $this->success([], "Ticket created successfully");
    }

    public function show($id)
    {
        $ticket = Ticket::with([
            'company',
            'requester',
            'assignedToUser',
            'messages.sender',
            'messages.files'
        ])->find($id);

        if (!$ticket) {
            return $this->error(null, "Ticket not found", 404);
        }

        $data = [
            'ticket' => [
                'id'        => $ticket->id,
                'unique_id' => $ticket->unique_id,
                'subject'   => $ticket->subject,
                'type'      => $ticket->type,
                'room_id'   => $ticket->room_id,
                'status'    => $ticket->status,
                'date'      => $ticket->date,
                'action'    => $ticket->action,
            ],

            'company' => [
                'id'   => optional($ticket->company)->id,
                'name' => optional($ticket->company)->name,
                'logo' => asset(optional($ticket->company)->logo),
            ],
            'account' => [
                'id'        => optional($ticket->assignedToUser)->id,
                'name'      => optional($ticket->assignedToUser)->name,
                'email'     => optional($ticket->assignedToUser)->email,
                'phone'     => optional($ticket->assignedToUser)->phone,
                'user_type' => optional($ticket->assignedToUser)->user_type,
            ],

            'requester' => [
                'id'        => optional($ticket->requester)->id,
                'name'      => optional($ticket->requester)->name,
                'email'     => optional($ticket->requester)->email,
                'phone'     => optional($ticket->requester)->phone,
                'user_type' => optional($ticket->requester)->user_type,
            ],

            'conversation' => $ticket->messages->map(function ($msg) {
                return [
                    'id'         => $msg->id,
                    'type'       => $msg->message_type,
                    'text'       => $msg->message_text,
                    'is_read'    => $msg->is_read,
                    'read_at'    => $msg->read_at,
                    'created_at' => $msg->created_at->toDateTimeString(),

                    'sender' => [
                        'id'        => optional($msg->sender)->id,
                        'name'      => optional($msg->sender)->name,
                        'email'     => optional($msg->sender)->email,
                        'user_type' => optional($msg->sender)->user_type,
                    ],

                    'receiver' => optional($msg->sender)->user_type === 'admin'
                        ? 'company'
                        : 'admin',

                    'attachment' => $msg->attachment_path ? [
                        'path' => $msg->attachment_path,
                        'type' => $msg->attachment_type,
                        'size' => $msg->attachment_size,
                    ] : null,

                    // SAFE FILES LIST
                    'files' => $msg->files ? $msg->files->map(function ($file) {
                        return [
                            'path' => $file->file_path,
                            'type' => $file->file_type,
                            'size' => $file->file_size,
                        ];
                    }) : [],
                ];
            })
        ];

        return $this->success($data, "Ticket fetched successfully");
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = Validator::make($request->all(), [
            'status' => 'required|in:pending,in-progress,unsolved,solved',
        ]);

        if ($validated->fails()) {
            return $this->error(null, $validated->errors()->first(), 422);
        }

        $ticket = Ticket::find($id);
        if (!$ticket) {
            return $this->error(null, "Ticket not found", 404);
        }

        $ticket->update([
            'status' => $request->status,
            'action' => 'status-updated'
        ]);

        return $this->success([], "Ticket status updated");
    }

    public function destroy($id)
    {
        $ticket = Ticket::find($id);
        if (!$ticket) {
            return $this->error(null, "Ticket not found", 404);
        }

        $ticket->delete();

        return $this->success([], "Ticket deleted", 200);
    }
}
