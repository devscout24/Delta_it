<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use App\Models\Space;
use App\Models\SpaceSchedule;
use App\Models\SpaceScheduleDay;
use App\Models\SpaceSlot;

class RoomManagementController extends Controller
{
    use ApiResponse;

    // =========================
    // LIST SPACES
    // =========================
    public function index()
    {
        return $this->success(Space::latest()->get(), 'Spaces fetched');
    }

    // =========================
    // CREATE SPACE
    // =========================
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'capacity' => 'nullable|integer',
            'color' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $data = $validator->validated();

        return $this->success(
            Space::create($data),
            'Space created'
        );
    }

    // =========================
    // SHOW SPACE
    // =========================
    public function show($id)
    {
        $space = Space::with('schedules.days')->find($id);

        if (!$space) {
            return $this->error([], 'Not found', 404);
        }

        return $this->success($space, 'Details');
    }

    // =========================
    // UPDATE SPACE
    // =========================
    public function update(Request $request, $id)
    {
        $space = Space::find($id);

        if (!$space) {
            return $this->error([], 'Not found', 404);
        }

        $space->update($request->only([
            'name',
            'capacity',
            'color',
            'description'
        ]));

        return $this->success($space, 'Updated');
    }

    // =========================
    // ADD SCHEDULE + AUTO SLOT
    // =========================
    public function addSchedule(Request $request, $spaceId)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',

            'days' => 'required|array',
            'days.*.day' => 'required|string',
            'days.*.start_time' => 'required',
            'days.*.end_time' => 'required',

            'duration' => 'required|integer|min:5'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        DB::beginTransaction();

        try {
            $schedule = SpaceSchedule::create([
                'space_id' => $spaceId,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);

            foreach ($request->days as $day) {
                SpaceScheduleDay::create([
                    'schedule_id' => $schedule->id,
                    'day_of_week' => strtolower($day['day']),
                    'start_time' => $day['start_time'],
                    'end_time' => $day['end_time'],
                ]);
            }

            $this->generateSlots($spaceId, $schedule, $request->days, $request->duration);

            DB::commit();

            return $this->success([], 'Schedule created');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error([], $e->getMessage(), 500);
        }
    }

    // =========================
    // SLOT GENERATION 🔥
    // =========================
    private function generateSlots($spaceId, $schedule, $days, $duration)
    {
        $start = Carbon::parse($schedule->start_date);
        $end = Carbon::parse($schedule->end_date);

        while ($start->lte($end)) {

            $dayName = strtolower($start->format('l'));

            foreach ($days as $day) {

                if ($dayName === strtolower($day['day'])) {

                    $current = Carbon::parse($day['start_time']);
                    $endTime = Carbon::parse($day['end_time']);

                    while ($current->lt($endTime)) {

                        $slotEnd = $current->copy()->addMinutes($duration);

                        if ($slotEnd->gt($endTime)) break;

                        $exists = SpaceSlot::where([
                            'space_id' => $spaceId,
                            'date' => $start->toDateString(),
                            'start_time' => $current->format('H:i')
                        ])->exists();

                        if (!$exists) {
                            SpaceSlot::create([
                                'space_id' => $spaceId,
                                'date' => $start->toDateString(),
                                'start_time' => $current->format('H:i'),
                                'end_time' => $slotEnd->format('H:i'),
                                'is_booked' => false
                            ]);
                        }

                        $current->addMinutes($duration);
                    }
                }
            }

            $start->addDay();
        }
    }

    // =========================
    // GET SLOTS
    // =========================
    public function getSlots(Request $request, $spaceId)
    {
        $query = SpaceSlot::where('space_id', $spaceId);

        if ($request->date) {
            $query->where('date', $request->date);
        }

        return $this->success($query->orderBy('date')->get(), 'Slots');
    }

    // =========================
    // CALENDAR
    // =========================
    public function calendar()
    {
        $slots = SpaceSlot::with('space')->get();

        return $this->success($slots->map(function ($s) {
            return [
                'space' => $s->space->name,
                'date' => $s->date,
                'start_time' => $s->start_time,
                'end_time' => $s->end_time,
                'is_booked' => $s->is_booked
            ];
        }), 'Calendar');
    }
}
