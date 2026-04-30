<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\Company;
use App\Models\User;
use App\Models\Room;

class AdminTicketController extends Controller
{
    use ApiResponse;

    // ======================
    // LIST ALL TICKETS
    // ======================
    public function index(Request $request)
    {
        $query = Ticket::with(['company', 'user']);

        // filter status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // search
        if ($request->filled('search')) {
            $query->where('subject', 'like', '%' . $request->search . '%');
        }

        $tickets = $query->latest()->paginate(10);

        $data = $tickets->getCollection()->map(function ($t) {
            return [
                'id' => $t->id,
                'subject' => $t->subject,
                'type' => $t->type,
                'status' => ucfirst(str_replace('_', ' ', $t->status)),
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
    // SHOW (DETAILS + CHAT)
    // ======================
    public function show($id)
    {
        $ticket = Ticket::with([
            'company',
            'user',
            'room',
            'messages.user'
        ])->find($id);

        if (!$ticket) {
            return $this->error([], 'Ticket not found', 404);
        }

        return $this->success([
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'type' => $ticket->type,
            'status' => ucfirst(str_replace('_', ' ', $ticket->status)),

            'company' => $ticket->company?->name,
            'requester' => $ticket->user?->name,
            'room' => $ticket->room?->name,

            'messages' => $ticket->messages->map(function ($m) {
                return [
                    'id' => $m->id,
                    'message' => $m->message,
                    'file_url' => $m->file_path ? Storage::url($m->file_path) : null,
                    'sender' => $m->user?->name ?? 'Admin',
                    'time' => $m->created_at->format('h:i A'),
                ];
            })
        ], 'Ticket details');
    }

    // ======================
    // CREATE TICKET
    // ======================
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
            'user_id' => 'required|exists:users,id',
            'room_id' => 'nullable|exists:rooms,id',

            'subject' => 'required|string',
            'type' => 'required|string',

            'message' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $ticket = Ticket::create([
            'company_id' => $request->company_id,
            'user_id' => $request->user_id,
            'room_id' => $request->room_id,
            'subject' => $request->subject,
            'type' => $request->type,
            'status' => 'pending',
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => $request->message,
        ]);

        return $this->success([
            'id' => $ticket->id
        ], 'Ticket created', 201);
    }

    // ======================
    // REPLY (WITH ATTACHMENT)
    // ======================
    public function reply(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'nullable|string',
            'file' => 'nullable|file|max:4096'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $ticket = Ticket::find($id);

        if (!$ticket) {
            return $this->error([], 'Ticket not found', 404);
        }

        $filePath = null;

        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('tickets', 'public');
        }

        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => $request->message,
            'file_path' => $filePath,
        ]);

        return $this->success([
            'id' => $message->id,
            'message' => $message->message,
            'file_url' => $filePath ? Storage::url($filePath) : null
        ], 'Reply sent');
    }

    // ======================
    // UPDATE STATUS
    // ======================
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,in_progress,resolved,closed'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $ticket = Ticket::find($id);

        if (!$ticket) {
            return $this->error([], 'Ticket not found', 404);
        }

        $ticket->update([
            'status' => $request->status
        ]);

        return $this->success([], 'Status updated');
    }

    // ======================
    // GET COMPANIES
    // ======================
    public function companies()
    {
        $companies = Company::select('id', 'name')->get();

        return $this->success($companies, 'Companies list');
    }

    // ======================
    // GET USERS BY COMPANY
    // ======================
    public function companyUsers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $users = User::where('company_id', $request->company_id)
            ->select('id', 'name')
            ->get();

        return $this->success($users, 'Users list');
    }

    // ======================
    // GET ROOMS BY COMPANY
    // ======================
    public function rooms(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $rooms = Room::where('company_id', $request->company_id)
            ->select('id', 'name')
            ->get();

        return $this->success($rooms, 'Rooms list');
    }
}
