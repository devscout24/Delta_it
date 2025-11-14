<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    use ApiResponse;
    public function index(Request $request)
    {
        $tickets = Ticket::with('company', 'requester')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->company_id, fn($q) => $q->where('company_id', $request->company_id))
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($tickets, "Tickets fetched successfully");
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required',
            'company_id' => 'required|exists:companies,id',
            'type' => 'required',
        ]);

        $ticket = Ticket::create([
            'unique_id' => 'TIC-' . strtoupper(Str::random(8)),
            'subject' => $validated['subject'],
            'company_id' => $validated['company_id'],
            'requester_id' => auth()->id(),
            'type' => $validated['type'],
            'status' => 'pending',
            'room_id' => null,
            'action' => 'created'
        ]);

        return $this->success($ticket, "Ticket created successfully");
    }

    public function show($id)
    {
        $ticket = Ticket::with(['messages.sender', 'company'])->find($id);

        if (!$ticket) {
            return $this->error(null, "Ticket not found", 404);
        }

        return $this->success($ticket, "Ticket fetched successfully");
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in-progress,unsolved,solved,completed'
        ]);

        $ticket = Ticket::find($id);
        if (!$ticket) {
            return $this->error(null, "Ticket not found", 404);
        }

        $ticket->update([
            'status' => $validated['status'],
            'action' => 'status-updated'
        ]);

        return $this->success($ticket, "Ticket status updated");
    }
}
