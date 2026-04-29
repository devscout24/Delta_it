<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Support\Facades\Auth;

class RequestController extends Controller
{
    use ApiResponse;

    // ======================
    // LIST ALL REQUESTS
    // ======================
    public function index(Request $request)
    {
        $query = Ticket::with('company');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $tickets = $query->latest()->paginate(10);

        $data = $tickets->getCollection()->map(function ($t) {
            return [
                'id' => $t->id,
                'subject' => $t->subject,
                'type' => $t->type,
                'status' => $t->status,
                'created_at' => $t->created_at->format('d M Y'),

                'company' => [
                    'id' => $t->company?->id,
                    'name' => $t->company?->name,
                ],
            ];
        });

        return $this->success([
            'requests' => $data,
            'pagination' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'total' => $tickets->total(),
            ]
        ], 'Requests fetched');
    }

    // ======================
    // SHOW REQUEST DETAILS
    // ======================
    public function show($id)
    {
        $ticket = Ticket::with(['messages.user', 'company'])->find($id);

        if (!$ticket) {
            return $this->error([], 'Request not found', 404);
        }

        return $this->success([
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'type' => $ticket->type,
            'status' => $ticket->status,
            'company' => $ticket->company?->name,

            'messages' => $ticket->messages->map(function ($m) {
                return [
                    'id' => $m->id,
                    'message' => $m->message,
                    'sender' => $m->user?->name ?? 'Admin',
                    'created_at' => $m->created_at->format('h:i A'),
                ];
            })
        ], 'Request details');
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
            return $this->error([], 'Request not found', 404);
        }

        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(), // admin user
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
            return $this->error([], 'Request not found', 404);
        }

        $ticket->update([
            'status' => $request->status
        ]);

        return $this->success([], 'Status updated');
    }
}
