<?php

namespace App\Http\Controllers\Api;

use Exception;
use Carbon\Carbon;
use App\Models\Email;
use App\Models\Meeting;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;

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


            $meeting = Meeting::create([
                'meeting_name' => $request->meeting_name,
                'date' => $request->date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'room_id' => $request->room_id,
                'add_emails' => $request->add_emails,
                'meeting_type' => $request->meeting_type,
                'online_link' => $request->online_link,
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
}
