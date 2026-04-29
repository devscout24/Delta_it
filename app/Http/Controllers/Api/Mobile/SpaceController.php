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
        $spaces = Space::where('is_active', true)
            ->latest()
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'color' => $s->color,
                    'capacity' => $s->capacity,
                ];
            });

        return $this->success($spaces, 'Spaces fetched');
    }

    // ======================
    // SPACE DETAILS
    // ======================
    public function details($id)
    {
        $space = Space::find($id);

        if (!$space) {
            return $this->error([], 'Space not found', 404);
        }

        return $this->success([
            'id' => $space->id,
            'name' => $space->name,
            'location' => $space->name, // or separate later
            'max_invitees' => $space->capacity,
            'description' => $space->description,
        ], 'Space details');
    }

    // ======================
    // GET SLOTS
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
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'start_time' => $s->start_time,
                    'end_time' => $s->end_time,
                    'is_booked' => $s->is_booked,
                ];
            });

        return $this->success($slots, 'Slots fetched');
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

        $slot = SpaceSlot::find($request->slot_id);

        // 🚨 DOUBLE CHECK
        if ($slot->is_booked) {
            return $this->error([], 'Slot already booked', 422);
        }

        // ✅ create booking
        $booking = SpaceBooking::create([
            'space_id' => $request->space_id,
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'date' => $slot->date,
            'start_time' => $slot->start_time,
            'end_time' => $slot->end_time,
            'status' => 'pending',
        ]);

        // ⚠️ IMPORTANT CHANGE
        // DO NOT mark slot booked yet (wait for approval)
        // $slot->update(['is_booked' => true]);

        return $this->success($booking, 'Booking request submitted');
    }

    // ======================
    // MY BOOKINGS
    // ======================
    public function myBookings()
    {
        $user = Auth::guard('api')->user();

        $bookings = SpaceBooking::with('space')
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(function ($b) {
                return [
                    'id' => $b->id,
                    'space_name' => $b->space->name,
                    'date' => date('d M', strtotime($b->date)),
                    'start_time' => $b->start_time,
                    'end_time' => $b->end_time,
                    'status' => $b->status,
                ];
            });

        return $this->success($bookings, 'Bookings fetched');
    }
}
