<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use App\Models\Space;
use App\Models\SpaceSlot;
use App\Models\SpaceBooking;

class SpaceController extends Controller
{
    use ApiResponse;

    // ======================
    // LIST SPACES
    // ======================
    public function index()
    {
        return $this->success(
            Space::where('is_active', true)
                ->latest()
                ->get()
                ->map(fn($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'color' => $s->color,
                    'capacity' => $s->capacity,
                ]),
            'Spaces'
        );
    }

    // ======================
    // SPACE DETAILS
    // ======================
    public function details($id)
    {
        $space = Space::find($id);

        if (!$space) {
            return $this->error([], 'Not found', 404);
        }

        return $this->success([
            'id' => $space->id,
            'name' => $space->name,
            'location' => $space->name,
            'max_invitees' => $space->capacity,
            'description' => $space->description,
        ], 'Details');
    }

    // ======================
    // AVAILABLE DATES 🔥
    // ======================
    public function availableDates($id)
    {
        $dates = SpaceSlot::where('space_id', $id)
            ->where('is_booked', false)
            ->pluck('date')
            ->unique()
            ->values();

        return $this->success($dates, 'Available dates');
    }

    // ======================
    // SLOTS BY DATE
    // ======================
    public function slots(Request $request, $id)
    {
        $request->validate([
            'date' => 'required|date'
        ]);

        $slots = SpaceSlot::where('space_id', $id)
            ->where('date', $request->date)
            ->orderBy('start_time')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'start_time' => $s->start_time,
                'end_time' => $s->end_time,
                'is_booked' => $s->is_booked,
            ]);

        return $this->success($slots, 'Slots');
    }

    // ======================
    // BOOK SPACE
    // ======================
    public function book(Request $request)
    {
        $request->validate([
            'space_id' => 'required|exists:spaces,id',
            'slot_id' => 'required|exists:space_slots,id',
        ]);

        $user = Auth::guard('api')->user();

        if (!$user || !$user->company_id) {
            return $this->error([], 'Unauthorized', 401);
        }

        $slot = SpaceSlot::where('id', $request->slot_id)
            ->where('space_id', $request->space_id)
            ->first();

        if (!$slot) {
            return $this->error([], 'Invalid slot', 404);
        }

        if ($slot->is_booked) {
            return $this->error([], 'Already booked', 422);
        }

        // prevent duplicate pending
        $exists = SpaceBooking::where([
            'space_id' => $request->space_id,
            'date' => $slot->date,
            'start_time' => $slot->start_time,
            'user_id' => $user->id,
            'status' => 'pending'
        ])->exists();

        if ($exists) {
            return $this->error([], 'Already requested', 422);
        }

        $booking = SpaceBooking::create([
            'space_id' => $request->space_id,
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'date' => $slot->date,
            'start_time' => $slot->start_time,
            'end_time' => $slot->end_time,
            'status' => 'pending',
        ]);

        return $this->success($booking, 'Request submitted');
    }

    // ======================
    // MY BOOKINGS
    // ======================
    public function myBookings()
    {
        $user = Auth::guard('api')->user();

        $data = SpaceBooking::with('space')
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(fn($b) => [
                'id' => $b->id,
                'space' => $b->space->name,
                'date' => date('d M', strtotime($b->date)),
                'time' => $b->start_time . ' - ' . $b->end_time,
                'status' => ucfirst($b->status),
            ]);

        return $this->success($data, 'Bookings');
    }
}
