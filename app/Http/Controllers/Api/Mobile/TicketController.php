<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\RoomAllocation;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    use ApiResponse;

    // ======================
    // MOBILE LIST
    // ======================
    public function mobileIndex(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tab' => 'nullable|in:unsolved,in-progress,solved,all',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        $user = Auth::guard('api')->user();

        if (!$user || !$user->company_id) {
            return $this->error([], 'Unauthorized', 401);
        }

        $tab = $request->get('tab', 'all');
        $perPage = (int) ($request->get('per_page', 10));

        $baseQuery = Ticket::where('company_id', $user->company_id);

        $unsolved = (clone $baseQuery)->whereIn('status', ['unsolved', 'pending'])->count();
        $inProgress = (clone $baseQuery)->where('status', 'in-progress')->count();
        $solved = (clone $baseQuery)->where('status', 'solved')->count();

        $tickets = (clone $baseQuery)
            ->when($tab === 'unsolved', fn($q) => $q->whereIn('status', ['unsolved', 'pending']))
            ->when($tab === 'in-progress', fn($q) => $q->where('status', 'in-progress'))
            ->when($tab === 'solved', fn($q) => $q->where('status', 'solved'))
            ->latest()
            ->paginate($perPage);

        $tickets->getCollection()->transform(function ($t) {
            return [
                'id' => $t->id,
                'unique_id' => $t->unique_id,
                'subject' => $t->subject,
                'date' => $t->date ? date('d/m/Y', strtotime($t->date)) : null,
                'status' => $t->status,
                'status_badge' => $t->status === 'pending'
                    ? 'pending'
                    : ($t->status === 'unsolved' ? 'new' : $t->status),
            ];
        });

        return $this->success([
            'tabs' => [
                'unsolved' => $unsolved,
                'in_progress' => $inProgress,
                'solved' => $solved,
            ],
            'selected_tab' => $tab,
            'tickets' => $tickets->items(),
            'pagination' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ],
        ], 'Tickets fetched');
    }

    // ======================
    // CREATE TICKET
    // ======================
    public function mobileStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'type'    => 'required|in:maintenance,access,support,other',
            'room_id' => 'nullable|exists:rooms,id',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        $user = Auth::guard('api')->user();

        if (!$user || !$user->company_id) {
            return $this->error([], 'Unauthorized', 401);
        }

        // ✅ FIXED ROOM VALIDATION
        if ($request->filled('room_id')) {
            $exists = RoomAllocation::where('room_id', $request->room_id)
                ->where('company_id', $user->company_id)
                ->where('status', 'active')
                ->exists();

            if (!$exists) {
                return $this->error([], 'Invalid room for your company', 422);
            }
        }

        $ticket = DB::transaction(function () use ($request, $user) {

            $t = Ticket::create([
                'unique_id' => 'TIC-' . strtoupper(Str::random(8)),
                'company_id' => $user->company_id,
                'user_id' => $user->id,
                'requester_id' => $user->id,
                'requester_role' => $user->role === 'admin' ? 'admin' : 'user',
                'subject' => $request->subject,
                'type' => $request->type,
                'status' => 'pending',
                'room_id' => $request->room_id,
                'action' => 'created',
                'date' => now()->toDateString(),
            ]);

            TicketMessage::create([
                'ticket_id' => $t->id,
                'user_id' => $user->id,
                'sender_id' => $user->id,
                'message' => $request->message,
                'message_type' => 'text',
                'message_text' => $request->message,
                'is_read' => false,
            ]);

            return $t;
        });

        return $this->success([
            'id' => $ticket->id,
            'unique_id' => $ticket->unique_id,
            'subject' => $ticket->subject,
            'status' => $ticket->status,
        ], 'Ticket created', 201);
    }

    // ======================
    // SHOW TICKET
    // ======================
    public function mobileShow($id)
    {
        $user = Auth::guard('api')->user();

        if (!$user || !$user->company_id) {
            return $this->error([], 'Unauthorized', 401);
        }

        $ticket = Ticket::with([
            'requester:id,name,email,role',
            'messages.sender:id,name,email,role',
            'messages.files:id,ticket_message_id,file_path,file_type,file_size',
        ])
            ->where('company_id', $user->company_id)
            ->find($id);

        if (!$ticket) {
            return $this->error([], 'Ticket not found', 404);
        }

        $conversation = $ticket->messages
            ->sortBy('created_at')
            ->map(function ($m) use ($user) {
                return [
                    'id' => $m->id,
                    'type' => $m->message_type,
                    'text' => $m->message_text,
                    'time' => $m->created_at?->format('g:i A'),
                    'is_me' => $m->sender_id === $user->id,
                    'files' => $m->files->map(fn($f) => [
                        'path' => asset($f->file_path),
                        'type' => $f->file_type,
                    ]),
                ];
            });

        return $this->success([
            'ticket' => [
                'id' => $ticket->id,
                'title' => '#' . $ticket->id . ' - ' . $ticket->subject,
                'status' => $ticket->status,
                'type' => $ticket->type,
                'date' => $ticket->date,
            ],
            'conversation' => $conversation,
        ], 'Ticket details');
    }

    // ======================
    // SEND MESSAGE
    // ======================
    public function mobileSendMessage(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:text,attachment',
            'message' => 'nullable|string',
            'files.*' => 'file|max:20480',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        $user = Auth::guard('api')->user();

        if (!$user || !$user->company_id) {
            return $this->error([], 'Unauthorized', 401);
        }

        $ticket = Ticket::where('company_id', $user->company_id)->find($id);

        if (!$ticket) {
            return $this->error([], 'Ticket not found', 404);
        }

        $message = DB::transaction(function () use ($request, $user, $ticket) {

            $msg = TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'sender_id' => $user->id,
                'message' => $request->message ?? '',
                'message_type' => $request->type,
                'message_text' => $request->message,
                'is_read' => false,
            ]);

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {

                    $path = $this->fileUpload($file, 'ticket_files');

                    TicketAttachment::create([
                        'ticket_message_id' => $msg->id,
                        'file_path' => $path,
                    ]);
                }
            }

            return $msg;
        });

        return $this->success([
            'id' => $message->id,
            'text' => $message->message_text,
        ], 'Message sent', 201);
    }
}
