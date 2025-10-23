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
use App\Http\Controllers\Controller;
use Monolog\Handler\ElasticaHandler;

class MeetingController extends Controller
{
    use ApiResponse;
    public function store(Request $request)
    {
        // dd($request->add_emails);
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

            if($request->meeting_type =='online'){
            $online_link = $request->online_link;
            }else{
                $online_link = null;
            }      
            
            if($request->meeting_type =='physical'){
                $room_id = $request->room_id;
            }else{
                $room_id = null;
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

        // dd($filter, $date);
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

        return response()->json([
            'status' => true,
            'message' => 'Meetings fetched successfully',
            'filter' => $filter,
            'selected_date' => $date ?? null,
            'data' => $meetings,
        ], 200);
    }


    function getSingleMeeting($id){
        
        $meeting = Meeting::select('id','meeting_name','date','start_time','end_time','meeting_type')
        ->with(['room','appointmentSlots'])
        ->where('id', $id)
        ->get();

        if (empty($meeting)) {
            return $this->error('Meeting not found', 404);
        }
        return $this->success($meeting, 'Meeting fetched successfully', 200);
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
                'meeting_id' => $event->meeting_id,
                'room_id' => $event->room_id,
                'room' => [
                    'id' => $event->room?->id,
                    'room_name' => $event->room?->room_name,
                ],
                'meeting' => [
                    'id' => $event->meeting?->id,
                    'meeting_name' => $event->meeting?->meeting_name,
                    'date' => $event->meeting?->date,

                ],
            ];
        });
    
        return $this->success($formattedEvents, 'Events fetched successfully', 200);
    }
    
    
}

