<?php

namespace App\Http\Controllers\Api;

use Exception;
use Carbon\Carbon;
use App\Models\Email;
use App\Models\Meeting;
use App\Models\Appointment;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\MettingBookRequest;
use App\Http\Controllers\Controller;
use App\Mail\MeetingNotificationMail;
use App\Models\MeetingEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

class MeetingController extends Controller
{
    use ApiResponse;

    public function todaysMeetings(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (!$user || !$user->company_id) {
            return $this->error([], "User does not belong to a company", 403);
        }

        $companyId = $user->company_id;
        $today     = Carbon::today()->format('Y-m-d');

        // Auto complete old meetings
        Meeting::whereDate('date', '<', $today)
            ->where('status', 'pending')
            ->update(['status' => 'completed']);

        // Get meetings for user's company happening today
        $meetings = Meeting::where('company_id', $companyId)
            ->orderBy('start_time')
            ->get();

        $meetings = $meetings->map(function ($meeting) {
            return [
                'id'           => $meeting->id,
                'meeting_name' => $meeting->meeting_name,
                'date'         => $meeting->date,
                'location'     => $meeting->location,
                'online_link'  => $meeting->online_link,
                'start_time'   => $meeting->start_time,
                'end_time'     => $meeting->end_time,
                'status'       => $meeting->status,
                'creator'      => $meeting->creator ? [
                    'id'    => $meeting->creator->id,
                    'profile_photo'  => $meeting->creator->profile_photo ?? asset('default/avatar.png'),
                ] : null,
            ];
        });

        return $this->success([
            'date'     => $today,
            'company_id' => $companyId,
            'meetings' => $meetings
        ], "Today's meetings fetched successfully");
    }

    public function index_mobile()
    {
        // Auto complete old meetings
        Meeting::whereDate('date', '<', Carbon::today())
            ->where('status', 'pending')
            ->update(['status' => 'completed']);

        // Fetch meetings
        $meetings = Meeting::orderBy('date', 'desc')
            ->orderBy('start_time', 'asc')
            ->get();

        return $this->success([
            'meetings' => $meetings
        ], "Meetings fetched successfully", 200);
    }


    public function index(Request $request)
    {
        // ---- VALIDATION ----
        $validator = Validator::make($request->all(), [
            'date'       => 'required|date_format:d-m-Y',
            'start_time' => 'nullable|date_format:H:i',
            'end_time'   => 'nullable|date_format:H:i',
            'mode'       => 'nullable|in:day,week,month',
            'month'      => 'nullable|date_format:Y-m'
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        // Convert validated date to Carbon safely
        $startDate = Carbon::createFromFormat('d-m-Y', $request->date);

        // Auto complete old meetings
        Meeting::whereDate('date', '<', Carbon::today())
            ->where('status', 'pending')
            ->update(['status' => 'completed']);

        // Base query
        $query = Meeting::query();

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('meeting_type')) {
            $query->where('meeting_type', $request->meeting_type);
        }

        if ($request->filled('location')) {
            $query->where('location', $request->location);
        }

        $mode = $request->get('mode', 'day');

        // ----------------------------------------------------------
        // DAY MODE
        // ----------------------------------------------------------
        if ($mode === 'day') {

            $day = $startDate->copy()->format('Y-m-d');

            $meetings = (clone $query)
                ->whereDate('date', $day)
                ->orderBy('start_time')
                ->get();

            // Generate hourly slots (9-10, 10-11, etc.)
            $slots = [];
            for ($h = 8; $h <= 18; $h++) {
                $slots[] = sprintf('%02d:00-%02d:00', $h, $h + 1);
            }

            // Count meetings per slot
            $slotCounts = [];
            foreach ($slots as $slot) {
                [$s, $e] = explode('-', $slot);
                $slotCounts[$slot] = $meetings->filter(function ($m) use ($s, $e) {
                    return !($m->end_time <= $s || $m->start_time >= $e);
                })->count();
            }

            return $this->success([
                'mode' => 'day',
                'date' => $day,
                'slots' => $slots,
                'slot_counts' => $slotCounts,
                'meetings' => $meetings
            ], "Day meetings fetched", 200);
        }

        // ----------------------------------------------------------
        // WEEK MODE
        // ----------------------------------------------------------
        if ($mode === 'week') {

            $start = $startDate->copy()->startOfDay();
            $end   = $start->copy()->addDays(6)->endOfDay();

            $meetings = (clone $query)
                ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                ->orderBy('date')->orderBy('start_time')
                ->get();

            // Week dates
            $dates = [];
            for ($i = 0; $i < 7; $i++) {
                $dates[] = $start->copy()->addDays($i)->format('Y-m-d');
            }

            // Time slots
            $slots = [];
            for ($h = 8; $h <= 18; $h++) {
                $slots[] = sprintf('%02d:00-%02d:00', $h, $h + 1);
            }

            // Matrix (slot × date)
            $matrix = [];
            foreach ($slots as $slot) {
                [$s, $e] = explode('-', $slot);
                $row = [];

                foreach ($dates as $d) {
                    $count = $meetings->filter(function ($m) use ($d, $s, $e) {
                        if ($m->date !== $d) return false;
                        return !($m->end_time <= $s || $m->start_time >= $e);
                    })->count();
                    $row[] = $count;
                }

                $matrix[$slot] = $row;
            }

            return $this->success([
                'mode' => 'week',
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d'),
                'dates' => $dates,
                'slots' => $slots,
                'matrix' => $matrix,
                'meetings_grouped' => $meetings->groupBy('date')->map->values()
            ], "Week meetings fetched", 200);
        }

        // ----------------------------------------------------------
        // MONTH MODE
        // ----------------------------------------------------------
        if ($mode === 'month') {

            if ($request->filled('month')) {
                $monthStart = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();
            } else {
                $monthStart = $startDate->copy()->startOfMonth();
            }

            $monthEnd = $monthStart->copy()->endOfMonth();

            $meetings = (clone $query)
                ->whereBetween('date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
                ->orderBy('date')->orderBy('start_time')
                ->get();

            $days = [];
            $dayCounts = [];
            $grouped = $meetings->groupBy('date');

            for ($d = $monthStart->copy(); $d->lte($monthEnd); $d->addDay()) {
                $dateStr = $d->format('Y-m-d');
                $days[] = $dateStr;
                $dayCounts[$dateStr] = ($grouped[$dateStr] ?? collect())->count();
            }

            return $this->success([
                'mode' => 'month',
                'month' => $monthStart->format('Y-m'),
                'days' => $days,
                'day_counts' => $dayCounts,
                'meetings_grouped' => $grouped->map->values()
            ], "Month meetings fetched", 200);
        }

        // ---------- DEFAULT ----------
        $meetings = $query->orderBy('date', 'desc')->get();
        return $this->success($meetings, "Meetings fetched successfully", 200);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id'   => 'required|exists:companies,id',
            'meeting_name' => 'required|string',
            'date'         => 'required|date',
            'start_time'   => 'required',
            'end_time'     => 'required',
            'meeting_type' => 'required|in:virtual,office',
            'online_link'  => 'nullable|string',
            'location'     => 'nullable|string',
            'add_emails'   => 'nullable|array',
            'add_emails.*' => 'email',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        // Validation rules based on meeting type
        if ($request->meeting_type === 'virtual' && !$request->online_link) {
            return $this->error([], "Online meeting link is required for virtual meetings", 422);
        }

        if ($request->meeting_type === 'office' && !$request->location) {
            return $this->error([], "Location is required for office meetings", 422);
        }
        $date = \Carbon\Carbon::createFromFormat('d-m-Y', $request->date)->format('Y-m-d');

        $meeting = Meeting::create([
            'company_id'   => $request->company_id,
            'created_by'   => Auth::guard('api')->user()->id,
            'meeting_name' => $request->meeting_name,
            'date'         => $date,
            'start_time'   => $request->start_time,
            'end_time'     => $request->end_time,
            'meeting_type' => $request->meeting_type,
            'online_link'  => $request->meeting_type === 'virtual' ? $request->online_link : null,
            'location'     => $request->meeting_type === 'office' ? $request->location : null,
            'status'       => 'pending',
        ]);


        // Store emails into separate table
        // Store emails into separate table + Send mails
        if (!empty($request->add_emails)) {
            foreach ($request->add_emails as $email) {

                // Save email
                MeetingEmail::create([
                    'meeting_id' => $meeting->id,
                    'email'      => $email,
                ]);

                // Send mail
                Mail::to($email)->send(new MeetingNotificationMail($meeting, $email));
            }
        }


        return $this->success($meeting, "Meeting created successfully", 201);
    }

    public function update(Request $request, $id)
    {
        $meeting = Meeting::find($id);

        if (!$meeting) {
            return $this->error([], "Meeting not found", 404);
        }

        $validator = Validator::make($request->all(), [
            'company_id'   => 'required|exists:companies,id',
            'meeting_name' => 'required|string',
            'date'         => 'required|string',
            'start_time'   => 'required',
            'end_time'     => 'required',
            'meeting_type' => 'required|in:virtual,office',
            'online_link'  => 'nullable|string',
            'location'     => 'nullable|string',
            'add_emails'   => 'nullable|array',
            'add_emails.*' => 'email',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        // Virtual meeting must have link
        if ($request->meeting_type === 'virtual' && !$request->online_link) {
            return $this->error([], "Online meeting link is required for virtual meetings", 422);
        }

        // Office meeting must have location
        if ($request->meeting_type === 'office' && !$request->location) {
            return $this->error([], "Location is required for office meetings", 422);
        }

        // Convert date format d-m-Y to Y-m-d
        try {
            $date = \Carbon\Carbon::createFromFormat('d-m-Y', $request->date)->format('Y-m-d');
        } catch (\Exception $e) {
            return $this->error([], "Invalid date format. Expected d-m-Y", 422);
        }

        // Update meeting data
        $meeting->update([
            'company_id'   => $request->company_id,
            'meeting_name' => $request->meeting_name,
            'date'         => $date,
            'start_time'   => $request->start_time,
            'end_time'     => $request->end_time,
            'meeting_type' => $request->meeting_type,
            'online_link'  => $request->meeting_type === 'virtual' ? $request->online_link : null,
            'location'     => $request->meeting_type === 'office' ? $request->location : null,
        ]);

        // -----------------------------
        // UPDATE MEETING EMAILS
        // -----------------------------
        MeetingEmail::where('meeting_id', $meeting->id)->delete();

        if (!empty($request->add_emails)) {
            foreach ($request->add_emails as $email) {
                MeetingEmail::create([
                    'meeting_id' => $meeting->id,
                    'email'      => $email,
                ]);
            }
        }

        return $this->success($meeting, "Meeting updated successfully", 200);
    }

    public function details($id)
    {
        $meeting = Meeting::find($id);

        if (!$meeting) {
            return $this->error([], "Meeting not found", 404);
        }

        $meeting->emails = MeetingEmail::where('meeting_id', $meeting->id)->get();

        $data = [
            'id'           => $meeting->id,
            'company_id'   => $meeting->company_id,
            'meeting_name' => $meeting->meeting_name,
            'date'         => $meeting->date,
            'start_time'   => $meeting->start_time,
            'end_time'     => $meeting->end_time,
            'meeting_type' => $meeting->meeting_type,
            'online_link'  => $meeting->online_link,
            'location'     => $meeting->location,
            'emails'       => $meeting->emails->map(function ($email) {
                return [
                    'email' => $email->email
                ];
            })
        ];

        return $this->success($data, "Meeting details", 200);
    }


    /**
     * UPDATE MEETING STATUS
     */
    public function updateStatus(Request $request)
    {
        $request->validate([
            'id'     => 'required|exists:meetings,id',
            'status' => 'required|in:pending,completed,cancelled'
        ]);

        $meeting = Meeting::find($request->id);
        $meeting->status = $request->status;
        $meeting->save();

        return $this->success($meeting, "Meeting status updated", 200);
    }

    public function requestMeeting(Request $request)
    {
        // Ensure authenticated user and attach their company if available
        $user = Auth::guard('api')->user();
        if (!$user) {
            return $this->error([], 'Unauthenticated', 401);
        }

        if ($user->company_id) {
            // force company to user's company
            $request->merge(['company_id' => $user->company_id]);
        }

        $validation = Validator::make($request->all(), [
            'room_id'      => 'nullable|exists:rooms,id',
            'meeting_name' => 'required|string',
            'company_id'   => 'required|exists:companies,id',
            'date'         => 'required|date',
            'start_time'   => 'required|date_format:H:i',
            'end_time'     => 'required|date_format:H:i|after:start_time',
            'status'       => 'nullable|string|in:pending,confirmed,cancelled,requested,approved,rejected',
        ]);

        if ($validation->fails()) {
            return $this->error([], $validation->errors()->first(), 422);
        }

        //  Create meeting request (store in Meeting as requested)
        $meeting = Meeting::create([
            'room_id'     => $request->room_id,
            'company_id'  => $request->company_id,
            'meeting_name' => $request->meeting_name,
            'created_by'  => $user->id,
            'date'        => $request->date,
            'start_time'  => $request->start_time,
            'end_time'    => $request->end_time,
            'status'      => $request->status ?? 'requested',
        ]);

        return $this->success($meeting, "Meeting Requested submitted successfully.", 201);
    }

    public function acceptMeeting($id)
    {
        $meeting = Meeting::find($id);

        if (!$meeting) {
            return $this->error([], "Meeting not found.", 404);
        }

        if (!in_array($meeting->status, ['requested', 'pending'])) {
            return $this->error([], "Only requested or pending meetings can be approved.", 422);
        }

        $meeting->update([
            'status' => 'approved'
        ]);

        return $this->success($meeting, "Meeting request approved successfully.", 200);
    }

    public function rejectMeeting($id)
    {
        $meeting = Meeting::find($id);

        if (!$meeting) {
            return $this->error([], "Meeting not found.", 404);
        }

        if (!in_array($meeting->status, ['requested', 'pending'])) {
            return $this->error([], "Only requested or pending meetings can be rejected.", 422);
        }

        $meeting->update([
            'status' => 'rejected'
        ]);

        return $this->success($meeting, "Meeting request rejected successfully.", 200);
    }

    public function cancelMeeting($id)
    {
        $meeting = Meeting::find($id);

        if (!$meeting) {
            return $this->error([], "Meeting not found.", 404);
        }

        // Allowed statuses before cancellation
        if (!in_array($meeting->status, ['approved', 'requested', 'pending'])) {
            return $this->error([], "Only approved, requested, or pending meetings can be cancelled.", 422);
        }

        $meeting->update([
            'status' => 'cancelled'
        ]);

        return $this->success($meeting, "Meeting cancelled successfully.", 200);
    }





























































    public function store(Request $request)
    {
        try {
            $request->validate([
                'meeting_name' => 'required',
                'date' => 'required',
                'start_time' => 'required',
                'end_time' => 'required',
                'add_emails' => 'required|array',
                'meeting_type' => 'required',
                'online_link' => 'nullable|url|unique:meetings,online_link',
                'room_id' => [
                    'nullable',
                    Rule::unique('meetings')->where(function ($query) use ($request) {
                        return $query->where('date', $request->date)
                            ->where('start_time', '<', $request->end_time)
                            ->where('end_time', '>', $request->start_time);
                    }),
                ],
            ]);

            if ($request->meeting_type == 'online') {
                $online_link = $request->online_link;
                $room_id = $request->room_id;
            }

            if ($request->meeting_type == 'physical') {
                $room_id = $request->room_id;
            }

            $meeting = Meeting::create([
                'meeting_name' => $request->meeting_name,
                'date' => $request->date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'room_id' => $room_id,
                'add_emails' => $request->add_emails,
                'meeting_type' => $request->meeting_type,
                'online_link' => $online_link,
            ]);

            if (is_array($request->add_emails)) {
                foreach ($request->add_emails as $email) {
                    Email::create([
                        'meeting_id' => $meeting->id,
                        'email' => $email,
                    ]);
                }
            }

            return $this->success($meeting, 'Meeting created successfully', 200);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function filter(Request $request)
    {
        $filter = $request->query('filter'); // day, week, month
        $date   = $request->query('date');   // user-defined date

        $query = Meeting::query();

        if ($date) {
            $parsedDate = Carbon::parse($date)->toDateString();
            $query->whereDate('date', $parsedDate);
        } elseif ($filter === 'week') {

            $query->whereBetween('date', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
            ]);
        } elseif ($filter === 'month') {


            $query->whereMonth('date', Carbon::now()->month)
                ->whereYear('date', Carbon::now()->year);
        }

        $meetings = $query->orderBy('date', 'asc')->get();

        if ($meetings->isEmpty()) {
            return $this->error([], 'Meeting not fond', 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Meetings fetched successfully',
            'filter' => $filter,
            'selected_date' => $date ?? null,
            'data' => $meetings,
        ], 200);
    }


    public function singleMeeting($id)
    {
        if (!$id) {
            return $this->error([], 'Id not fond', 404);
        }
        $meeting =  Meeting::where('id', $id)->first();
        if (! $meeting) {
            return $this->error([], 'Meeting data not fond', 404);
        }

        return $this->error($meeting, 'Meeting fetched successfully', 201);
    }



    public function filterMeetingBytype(Request $request)
    {


        $query = Meeting::with('room');

        // Meeting type
        if ($request->has('meeting_type') && !empty($request->meeting_type)) {
            $query->where('meeting_type', $request->meeting_type);
        }

        // Room name
        if ($request->has('room_name') && !empty($request->room_name)) {
            $query->whereHas('room', function ($q) use ($request) {
                $q->where('room_name', 'like', '%' . $request->room_name . '%');
            });
        }

        $meetings = $query->get();

        if ($meetings->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No meetings found for given filters',
                'data' => [],
                'code' => 404
            ]);
        }

        $formattedMeetings = $meetings->map(function ($meeting) {
            return [
                'id' => $meeting->id,
                'meeting_name' => $meeting->meeting_name,
                'date' => $meeting->date,
                'start_time' => $meeting->start_time,
                'end_time' => $meeting->end_time,
                'meeting_type' => $meeting->meeting_type,
                'online_link' => $meeting->online_link,
                'room_name' => optional($meeting->room)->room_name,
                'add_emails' => $meeting->add_emails,
            ];
        });

        return $this->success(
            $formattedMeetings,
            'Meetings filtered successfully',
            200
        );
    }

    public function getAllMeeting(Request $request)
    {
        try {
            $type = $request->query('type');

            $query =  Meeting::with(['room:id,floor,room_name,area,status', 'creator:id,profile_photo'])
                ->select('id', 'meeting_name', 'date', 'start_time', 'end_time', 'meeting_type', 'room_id', 'online_link', 'created_by');

            if (in_array($type, ['virtual', 'office'])) {
                $query->where('meeting_type', $type);
            }

            $meetings = $query->get();

            if ($meetings->isEmpty()) {
                return $this->error([], 'No meetings found', 404);
            }

            // Transform data
            $meetings = $meetings->map(function ($item) {
                $data = [
                    'id'           => $item->id,
                    'meeting_name' => $item->meeting_name,
                    'date'         => $item->date,
                    'start_time'   => $item->start_time,
                    'end_time'     => $item->end_time,
                    'meeting_type' => $item->meeting_type,
                    'admin_avatar' => $item->creator->profile_photo == null ? asset('default/avatar.png') : asset($item->creator->profile_photo),
                ];

                if ($item->meeting_type === 'virtual') {
                    $data['online_link'] = $item->online_link;
                } else {
                    $data['room'] = $item->room ? [
                        'room_id'   => $item->room->id,
                        'floor'     => $item->room->floor,
                        'room_name' => $item->room->room_name,
                        'area'      => $item->room->area,
                        'status'    => $item->room->status,
                    ] : null;
                }

                return $data;
            });

            return $this->success($meetings, 'Meetings fetched successfully', 200);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 'Server Error', 500);
        }
    }


    function getAllEvents()
    {
        $events = Appointment::with(['room', 'meeting'])->get();

        if ($events->isEmpty()) {
            return $this->error('Events not found', 404);
        }

        $formattedEvents = $events->map(function ($event) {
            return [
                'id' => $event->id,
                // 'meeting_id' => $event->meeting_id,
                // 'room_id' => $event->room_id,
                'room' => [
                    'id' => $event->room?->id,
                    'room_name' => $event->room?->room_name,

                ],
                'meeting' => [
                    'id' => $event->meeting?->id,
                    'meeting_name' => $event->meeting?->meeting_name,
                    'date' => $event->meeting?->date,
                    'duration' => $event->duration,
                    'event_color' => $event->event_color

                ],
            ];
        });

        return $this->success($formattedEvents, 'Events fetched successfully', 200);
    }

    public function getmeetingRequest()
    {
        // Include Meeting model requests (status requested/pending) and MettingBookRequest
        $meetingModelRequests = Meeting::whereIn('status', ['requested', 'pending'])
            ->with(['room', 'company', 'creator'])
            ->get();

        $mettingBookRequests = MettingBookRequest::all();

        if ($meetingModelRequests->isEmpty() && $mettingBookRequests->isEmpty()) {
            return $this->error([], 'No meeting requests found.', 404);
        }

        return $this->success([
            'meeting_model_requests' => $meetingModelRequests,
            'metting_book_requests'  => $mettingBookRequests,
        ], 'All meeting requests retrieved successfully.', 200);
    }
}
