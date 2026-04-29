<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\Company;

class AdminTicketController extends Controller
{
    use ApiResponse;

    // ======================
    // LIST ALL TICKETS
    // ======================
    public function index(Request $request)
    {
        $query = Ticket::with(['company', 'user']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('subject', 'like', '%' . $request->search . '%');
        }

        $tickets = $query->latest()->paginate(10);

        $data = $tickets->getCollection()->map(function ($t) {
            return [
                'id' => $t->id,
                'subject' => $t->subject,
                'type' => $t->type,
                'status' => $t->status,
                'created_at' => $t->created_at->format('d M Y'),

                'company' => $t->company?->name,
                'requester' => $t->user?->name,
            ];
        });

        return $this->success([
            'data' => $data,
            'pagination' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'total' => $tickets->total(),
            ]
        ], 'Tickets fetched');
    }

    // ======================
    // SHOW (CHAT VIEW)
    // ======================
    public function show($id)
    {
        $ticket = Ticket::with([
            'company',
            'user',
            'messages.user'
        ])->find($id);

        if (!$ticket) {
            return $this->error([], 'Ticket not found', 404);
        }

        return $this->success([
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'type' => $ticket->type,
            'status' => $ticket->status,

            'company' => $ticket->company?->name,
            'requester' => $ticket->user?->name,

            'messages' => $ticket->messages->map(function ($m) {
                return [
                    'id' => $m->id,
                    'message' => $m->message,
                    'sender' => $m->user?->name ?? 'Admin',
                    'created_at' => $m->created_at->format('h:i A'),
                ];
            })
        ], 'Ticket details');
    }

    // ======================
    // CREATE (ADMIN)
    // ======================
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
            'subject' => 'required|string',
            'type' => 'required|string',
            'message' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $ticket = Ticket::create([
            'company_id' => $request->company_id,
            'user_id' => Auth::id(), // admin creating
            'subject' => $request->subject,
            'type' => $request->type,
            'status' => 'pending',
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => $request->message,
        ]);

        return $this->success($ticket, 'Ticket created', 201);
    }

    // ======================
    // REPLY
    // ======================
    public function reply(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string'
        ]);

        $ticket = Ticket::find($id);

        if (!$ticket) {
            return $this->error([], 'Ticket not found', 404);
        }

        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => $request->message,
        ]);

        return $this->success($message, 'Reply sent');
    }

    // ======================
    // UPDATE STATUS
    // ======================
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,in_progress,resolved,closed'
        ]);

        $ticket = Ticket::find($id);

        if (!$ticket) {
            return $this->error([], 'Ticket not found', 404);
        }

        $ticket->update([
            'status' => $request->status
        ]);

        return $this->success([], 'Status updated');
    }
}
