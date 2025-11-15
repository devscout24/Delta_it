<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
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
        $tickets = Ticket::with('company', 'requester')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->company_id, fn($q) => $q->where('company_id', $request->company_id))
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($tickets, "Tickets fetched successfully");
    }

    public function store(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'subject'    => 'required',
            'company_id' => 'required|exists:companies,id',
            'type'       => 'required',
            'room_id'    => 'nullable|exists:rooms,id',
        ]);

        if ($validated->fails()) {
            return $this->error(null, $validated->errors()->first(), 422);
        }

        $ticket = Ticket::create([
            'unique_id'    => 'TIC-' . uniqid(),
            'subject'      => $request->subject,
            'company_id'   => $request->company_id,
            'requester_id' => Auth::guard('api')->id(),
            'type'         => $request->type,
            'status'       => 'pending',
            'room_id'      => $request->room_id,
            'action'       => 'created',
            'date' => now()->toDateString(),
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
