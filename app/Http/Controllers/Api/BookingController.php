<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\RoomBookings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    // Get Booking Room
    use \App\Traits\ApiResponse;

    public function index()
    {
        $user = Auth::guard('api')->user();

        if (!$user || !$user->company_id) {
            return $this->error([], 'User not associated with any company', 403);
        }

        // Fetch bookings for the user's company with related room info
        $bookings = RoomBookings::where('company_id', $user->company_id)
            ->with(['room:id,name,location']) // adjust fields as per your rooms table
            ->select('id', 'room_id', 'booking_name', 'date', 'start_time', 'end_time', 'status')
            ->orderBy('date', 'desc')
            ->get();

        if ($bookings->isEmpty()) {
            return $this->error([], 'No bookings found for this company', 404);
        }

        // Transform data for a clean API response
        $data = $bookings->map(function ($item) {
            return [
                'id'           => $item->id,
                'booking_name' => $item->booking_name,
                'date'         => $item->date,
                'start_time'   => $item->start_time,
                'end_time'     => $item->end_time,
                'status'       => $item->status,
                'room'         => $item->room ? [
                    'id'       => $item->room->id,
                    'name'     => $item->room->name,
                    'location' => $item->room->location ?? null,
                ] : null,
            ];
        });

        return $this->success($data, 'Company bookings fetched successfully');
    }


    // Book Room

    public function bookRoom(Request $request)
    {
        try {
            $user = Auth::guard('api')->user();

            if (!$user || !$user->company_id) {
                return $this->error([], 'User not associated with any company', 403);
            }

            $validator = Validator::make($request->all(), [
                'room_id'     => 'required|exists:rooms,id',
                'date'        => 'required|date',
                'booking_name' => 'required|string|max:255',
                'start_time'  => 'required',
                'end_time'    => 'required|after:start_time',
                'description' => 'nullable|string',
                'add_emails'  => 'nullable|string', // could be CSV or JSON
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors(), 'Validation error', 422);
            }

            // Check if the room is already booked at the given time
            $conflict = RoomBookings::where('room_id', $request->room_id)
                ->where('date', $request->date)
                ->where(function ($query) use ($request) {
                    $query->whereBetween('start_time', [$request->start_time, $request->end_time])
                        ->orWhereBetween('end_time', [$request->start_time, $request->end_time]);
                })
                ->exists();

            if ($conflict) {
                return $this->error([], 'Room already booked for the selected time slot', 409);
            }

            // Create booking
            $booking = RoomBookings::create([
                'room_id'      => $request->room_id,
                'company_id'   => $user->company_id,
                'date'         => $request->date,
                'booking_name' => $request->booking_name,
                'start_time'   => $request->start_time,
                'end_time'     => $request->end_time,
                'description'  => $request->description,
                'add_emails'   => $request->add_emails,
                'booked_by'    => $user->id,
                'status'       => 'pending',
            ]);

            return $this->success($booking, 'Room booked successfully', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 'Server error', 500);
        }
    }
}
