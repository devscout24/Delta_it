<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\TicketMessageFile;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            'requester_role' => Auth::guard('api')->user()?->user_type === 'admin' ? 'admin' : 'user',
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

    public function mobileStore(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'room_id' => 'nullable|exists:rooms,id',
            'message' => 'required|string',
        ]);

        if ($validated->fails()) {
            return $this->error($validated->errors(), $validated->errors()->first(), 422);
        }

        $user = Auth::guard('api')->user();

        if (! $user) {
            return $this->error([], 'Unauthorized', 401);
        }

        if (! $user->company_id) {
            return $this->error([], 'You are not assigned to any company', 422);
        }

        if ($request->filled('room_id')) {
            $roomBelongsToCompany = Room::where('id', $request->room_id)
                ->where('company_id', $user->company_id)
                ->exists();

            if (! $roomBelongsToCompany) {
                return $this->error([], "The room_id {$request->room_id} is not assigned to your company id {$user->company_id}", 422);
            }
        }

        $ticket = DB::transaction(function () use ($request, $user) {
            $newTicket = Ticket::create([
                'unique_id' => 'TIC-' . strtoupper(Str::random(10)),
                'company_id' => $user->company_id,
                'requester_id' => $user->id,
                'requester_role' => $user->user_type === 'admin' ? 'admin' : 'user',
                'subject' => $request->subject,
                'type' => $request->type,
                'status' => 'pending',
                'room_id' => $request->room_id,
                'action' => 'created',
                'date' => now()->toDateString(),
            ]);

            TicketMessage::create([
                'ticket_id' => $newTicket->id,
                'sender_id' => $user->id,
                'message_type' => 'text',
                'message_text' => $request->message,
                'is_read' => false,
            ]);

            return $newTicket;
        });

        return $this->success([
            'id' => $ticket->id,
            'unique_id' => $ticket->unique_id,
            'subject' => $ticket->subject,
            'type' => $ticket->type,
            'status' => $ticket->status,
            'date' => $ticket->date,
        ], 'Ticket created successfully', 201);
    }

    public function mobileIndex(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'tab' => 'nullable|in:unsolved,in-progress,solved,all',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validated->fails()) {
            return $this->error($validated->errors(), $validated->errors()->first(), 422);
        }

        $user = Auth::guard('api')->user();

        if (! $user) {
            return $this->error([], 'Unauthorized', 401);
        }

        if (! $user->company_id) {
            return $this->error([], 'User is not assigned to a company', 422);
        }

        $selectedTab = $request->get('tab', 'all');
        $perPage = (int) ($request->get('per_page', 10));

        $baseQuery = Ticket::query()
            ->where('company_id', $user->company_id);

        $unsolvedCount = (clone $baseQuery)
            ->whereIn('status', ['unsolved', 'pending'])
            ->count();

        $inProgressCount = (clone $baseQuery)
            ->where('status', 'in-progress')
            ->count();

        $solvedCount = (clone $baseQuery)
            ->where('status', 'solved')
            ->count();

        $ticketsQuery = (clone $baseQuery)
            ->when($selectedTab === 'unsolved', function ($q) {
                $q->whereIn('status', ['unsolved', 'pending']);
            })
            ->when($selectedTab === 'in-progress', function ($q) {
                $q->where('status', 'in-progress');
            })
            ->when($selectedTab === 'solved', function ($q) {
                $q->where('status', 'solved');
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $ticketsQuery->getCollection()->transform(function ($ticket) {
            $badge = $ticket->status === 'pending' ? 'pending' : ($ticket->status === 'unsolved' ? 'new' : $ticket->status);

            return [
                'id' => $ticket->id,
                'unique_id' => $ticket->unique_id,
                'subject' => $ticket->subject,
                'date' => $ticket->date ? date('d/m/Y', strtotime($ticket->date)) : null,
                'status' => $ticket->status,
                'status_badge' => $badge,
            ];
        });

        return $this->success([
            'tabs' => [
                'unsolved' => $unsolvedCount,
                'in_progress' => $inProgressCount,
                'solved' => $solvedCount,
            ],
            'selected_tab' => $selectedTab,
            'tickets' => $ticketsQuery->items(),
            'pagination' => [
                'current_page' => $ticketsQuery->currentPage(),
                'last_page' => $ticketsQuery->lastPage(),
                'per_page' => $ticketsQuery->perPage(),
                'total' => $ticketsQuery->total(),
            ],
        ], 'Mobile tickets fetched successfully');
    }

    public function mobileShow($id)
    {
        $user = Auth::guard('api')->user();

        if (! $user) {
            return $this->error([], 'Unauthorized', 401);
        }

        if (! $user->company_id) {
            return $this->error([], 'User is not assigned to a company', 422);
        }

        $ticket = Ticket::with([
            'requester:id,name,email,user_type',
            'messages.sender:id,name,email,user_type,profile_photo',
            'messages.files:id,ticket_message_id,file_path,file_type,file_size',
        ])
            ->where('company_id', $user->company_id)
            ->find($id);

        if (! $ticket) {
            return $this->error([], 'Ticket not found', 404);
        }

        $conversation = $ticket->messages
            ->sortBy('created_at')
            ->values()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'type' => $message->message_type,
                    'text' => $message->message_text,
                    'time' => $message->created_at?->format('g:i A'),
                    'created_at' => $message->created_at?->toDateTimeString(),
                    'is_read' => (bool) $message->is_read,
                    'sender' => [
                        'id' => optional($message->sender)->id,
                        'name' => optional($message->sender)->name,
                        'email' => optional($message->sender)->email,
                        'user_type' => optional($message->sender)->user_type,
                        'avatar' => optional($message->sender)->profile_photo
                            ? asset(optional($message->sender)->profile_photo)
                            : null,
                        'is_me' => optional($message->sender)->id === Auth::guard('api')->id(),
                    ],
                    'files' => $message->files->map(function ($file) {
                        return [
                            'id' => $file->id,
                            'path' => asset(ltrim($file->file_path, '/')),
                            'type' => $file->file_type,
                            'size' => $file->file_size,
                        ];
                    })->values(),
                ];
            });

        return $this->success([
            'ticket' => [
                'id' => $ticket->id,
                'unique_id' => $ticket->unique_id,
                'title' => '#' . $ticket->id . ' - ' . $ticket->subject,
                'subject' => $ticket->subject,
                'type' => $ticket->type,
                'status' => $ticket->status,
                'status_badge' => $ticket->status === 'pending' ? 'pending' : ($ticket->status === 'unsolved' ? 'new' : $ticket->status),
                'date' => $ticket->date ? date('d/m/Y', strtotime($ticket->date)) : null,
                'requester' => [
                    'id' => optional($ticket->requester)->id,
                    'name' => optional($ticket->requester)->name,
                    'email' => optional($ticket->requester)->email,
                    'user_type' => optional($ticket->requester)->user_type,
                ],
            ],
            'conversation' => $conversation,
        ], 'Mobile ticket details fetched successfully');
    }

    public function mobileSendMessage(Request $request, $id)
    {
        $validated = Validator::make($request->all(), [
            'type' => 'required|in:text,emoji,link,attachment',
            'message' => 'nullable|string',
            'files' => 'nullable|array',
            'files.*' => 'file|max:20480',
            'file' => 'nullable|file|max:20480',
        ]);

        if ($validated->fails()) {
            return $this->error($validated->errors(), $validated->errors()->first(), 422);
        }

        $user = Auth::guard('api')->user();

        if (! $user) {
            return $this->error([], 'Unauthorized', 401);
        }

        if (! $user->company_id) {
            return $this->error([], 'User is not assigned to a company', 422);
        }

        $ticket = Ticket::where('company_id', $user->company_id)->find($id);

        if (! $ticket) {
            return $this->error([], 'Ticket not found', 404);
        }

        $hasAnyFile = $request->hasFile('file') || $request->hasFile('files');
        if (! $hasAnyFile && ! $request->filled('message')) {
            return $this->error([], 'Message text or file is required', 422);
        }

        if ($request->type === 'attachment' && ! $hasAnyFile && ! $request->filled('message')) {
            return $this->error([], 'Attachment type requires a file or message', 422);
        }

        $message = DB::transaction(function () use ($request, $user, $ticket) {
            $newMessage = TicketMessage::create([
                'ticket_id' => $ticket->id,
                'sender_id' => $user->id,
                'message_type' => $request->type,
                'message_text' => $request->message,
                'is_read' => false,
            ]);

            $files = [];
            if ($request->hasFile('file')) {
                $files[] = $request->file('file');
            }
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $uploadedFile) {
                    $files[] = $uploadedFile;
                }
            }

            foreach ($files as $uploadedFile) {
                $fileSize = $uploadedFile->getSize();
                $fileType = $uploadedFile->getClientMimeType();

                $uploadedPath = $this->uploadFile($uploadedFile, 'ticket_files');

                if (! $uploadedPath) {
                    throw new \RuntimeException('File upload failed');
                }

                TicketMessageFile::create([
                    'ticket_message_id' => $newMessage->id,
                    'file_path' => $uploadedPath,
                    'file_type' => $fileType,
                    'file_size' => $fileSize,
                ]);
            }

            return $newMessage->load(['sender:id,name,email,user_type,profile_photo', 'files:id,ticket_message_id,file_path,file_type,file_size']);
        });

        return $this->success([
            'id' => $message->id,
            'ticket_id' => $message->ticket_id,
            'type' => $message->message_type,
            'text' => $message->message_text,
            'time' => $message->created_at?->format('g:i A'),
            'sender' => [
                'id' => optional($message->sender)->id,
                'name' => optional($message->sender)->name,
                'email' => optional($message->sender)->email,
                'user_type' => optional($message->sender)->user_type,
                'avatar' => optional($message->sender)->profile_photo
                    ? asset(optional($message->sender)->profile_photo)
                    : null,
            ],
            'files' => $message->files->map(function ($file) {
                return [
                    'id' => $file->id,
                    'path' => asset(ltrim($file->file_path, '/')),
                    'type' => $file->file_type,
                    'size' => $file->file_size,
                ];
            })->values(),
        ], 'Message sent successfully', 201);
    }
}
