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
use Illuminate\Support\Facades\Validator;

class MeetingController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        // Auto-complete old meetings
        Meeting::whereDate('date', '<', Carbon::today())
            ->where('status', 'pending')
            ->update(['status' => 'completed']);

        $query = Meeting::query();

        // Filter: Company
        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        // Filter: Status (pending, completed, cancelled)
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Filter: Meeting type (virtual, office)
        if ($request->meeting_type) {
            $query->where('meeting_type', $request->meeting_type);
        }

        // Filter: Room (only office meetings have room)
        if ($request->room_id) {
            $query->where('room_id', $request->room_id);
        }

        $meetings = $query->orderBy('date', 'desc')->get();

        return $this->success($meetings, "Meetings fetched successfully", 200);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id'   => 'required|exists:companies,id',
            'room_id'      => 'nullable|exists:rooms,id',
            'meeting_name' => 'required|string',
            'date'         => 'required|date',
            'start_time'   => 'required',
            'end_time'     => 'required',
            'meeting_type' => 'required|in:virtual,office',
            'online_link'  => 'nullable|string',
            'add_emails'   => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        // Virtual meeting must have link
        if ($request->meeting_type === 'virtual' && !$request->online_link) {
            return $this->error([], "Online meeting link is required for virtual meetings", 422);
        }

        $meeting = Meeting::create([
            'company_id'   => $request->company_id,
            'room_id'      => $request->meeting_type == 'office' ? $request->room_id : null,
            'created_by'   => auth()->id(),
            'meeting_name' => $request->meeting_name,
            'date'         => $request->date,
            'start_time'   => $request->start_time,
            'end_time'     => $request->end_time,
            'meeting_type' => $request->meeting_type,
            'online_link'  => $request->meeting_type == 'virtual' ? $request->online_link : null,
            'add_emails'   => json_encode($request->add_emails),
            'status'       => 'pending',
        ]);

        return $this->success($meeting, "Meeting created successfully", 201);
    }

    /**
     * UPDATE MEETING
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'           => 'required|exists:meetings,id',
            'company_id'   => 'nullable|exists:companies,id',
            'room_id'      => 'nullable|exists:rooms,id',
            'meeting_name' => 'required|string',
            'date'         => 'required|date',
            'start_time'   => 'required',
            'end_time'     => 'required',
            'meeting_type' => 'required|in:virtual,office',
            'online_link'  => 'nullable|string',
            'add_emails'   => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        $meeting = Meeting::find($request->id);

        // Check meeting type logic
        if ($request->meeting_type === 'virtual' && !$request->online_link) {
            return $this->error([], "Online meeting link is required for virtual meetings", 422);
        }

        $meeting->update([
            'company_id'   => $request->company_id,
            'room_id'      => $request->meeting_type == 'office' ? $request->room_id : null,
            'meeting_name' => $request->meeting_name,
            'date'         => $request->date,
            'start_time'   => $request->start_time,
            'end_time'     => $request->end_time,
            'meeting_type' => $request->meeting_type,
            'online_link'  => $request->meeting_type == 'virtual' ? $request->online_link : null,
            'add_emails'   => json_encode($request->add_emails),
        ]);

        return $this->success($meeting, "Meeting updated successfully", 200);
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
        $validation = Validator::make($request->all(), [
            'room_id'     => 'required|exists:rooms,id',
            'company_id'  => 'required|exists:companies,id',
            'date'        => 'required|date',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i|after:start_time',
            'status'      => 'nullable|string|in:pending,confirmed,cancelled',
        ]);

        if ($validation->fails()) {
            return $this->error([], $validation->errors()->first(), 422);
        }

        //  Create meeting
        $meeting = Meeting::create([
            'room_id'     => $request->room_id,
            'company_id'  => $request->company_id,
            'date'        => $request->date,
            'start_time'  => $request->start_time,
            'end_time'    => $request->end_time,
            'status'      => $request->status ?? 'requested',
        ]);

        //  Return response
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
        $meetings = MettingBookRequest::all();

        if ($meetings->isEmpty()) {
            return $this->error(
                [],
                'No meeting requests found.',
                404
            );
        }

        return $this->success(
            $meetings,
            'All meeting requests retrieved successfully.',
            200
        );
    }

    // meeting request for mobile
    public function StoreMeeting(Request $request)
    {
        //  Validate request data
        $request->validate([
            'room_id'     => 'required|exists:rooms,id',
            'company_id'  => 'required|exists:companies,id',
            'date'        => 'required|date',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i|after:start_time',
            'status'      => 'nullable|string|in:pending,confirmed,cancelled',
        ]);

        //  Create meeting
        $meeting = MettingBookRequest::create([
            'room_id'     => $request->room_id,
            'company_id'  => $request->company_id,
            'date'        => $request->date,
            'start_time'  => $request->start_time,
            'end_time'    => $request->end_time,
            'status'      => $request->status ?? 'pending',
        ]);

        //  Return response
        return response()->json([
            'status'  => true,
            'message' => 'Meeting Requested submited successfully.',
            'data'    => $meeting,
        ], 201);
    }
}
