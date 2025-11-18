<?php

namespace App\Http\Controllers\Api;

use App\Models\Room;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class RoomController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $rooms = Room::select('id', 'floor', 'room_name', 'area', 'polygon_points', 'status')->get();
            // Eager load company relationship if needed here for the frontend to display details
            $rooms = Room::with('company')->select('id', 'floor', 'room_name', 'area', 'polygon_points', 'status', 'company_id')->get();
            return $this->success($rooms, 'Rooms fetched successfully', 200);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }

    public function addRoom(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'floor'             => 'required|string',
            'room_name'         => 'required|string|max:255',
            'area'              => 'required|numeric',
            'polygon_points'    => 'required|array', // Validates that the input is a PHP array (the nested array format)
            'company_id'        => 'nullable|integer|exists:companies,id', // Added for completeness, if used
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        // store validated input
        $data = $validator->validated();

        try {
            // Check for uniqueness on same floor
            if (Room::where('floor', $data['floor'])
                ->where('room_name', $data['room_name'])
                ->exists()
            ) {
                return $this->error([], 'Room name already exists on this floor', 422);
            }

            // FIX: The frontend sends a nested array format, so no custom conversion is needed.
            // We directly JSON encode the array received from the request.
            
            // REMOVED faulty conversion loop:
            /*
            $points = $data['polygon_points'];
            $converted = [];
            for ($i = 0; $i < count($points); $i += 2) {
                $converted[] = [
                    (float)$points[$i],
                    (float)$points[$i + 1]
                ];
            }
            */

            $room = Room::create([
                'floor'          => $data['floor'],
                'room_name'      => $data['room_name'],
                'area'           => $data['area'],
                // FIX: Use the original validated input directly, it's already a nested array
                'polygon_points' => json_encode($data['polygon_points']),
                'status'         => (isset($data['company_id']) && $data['company_id'] != null) ? 'occupied' : 'available',
                'company_id'     => $data['company_id'] ?? null,
            ]);

            return $this->success($room, 'Room added successfully', 201);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }


    // ================================================

    public function assignCompany(Request $request)
    {
        try {
            $validated = $request->validate([
                'room_id'    => 'required|exists:rooms,id',
                'company_id' => 'required|exists:companies,id',
            ]);

            $room = Room::find($validated['room_id']);

            if ($room->company_id) {
                return $this->error([], 'This room is already assigned to a company.', 409);
            }

            $room->update([
                'company_id' => $validated['company_id'],
                'status'     => 'occupied',
            ]);

            return $this->success($room->load('company'), 'Company assigned successfully', 200);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }

    public function showRoomDetails($id)
    {
        try {
            $roomDetails = Room::with('company')->find($id);

            if (!$roomDetails) {
                return $this->error([], 'Room not found.', 404);
            }

            return $this->success($roomDetails, 'Room details fetched successfully', 200);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }

    public function roomStatusChange($status, $id)
    {
        try {
            $room = Room::find($id);

            if (!$room) {
                return $this->error([], 'Room not found', 404);
            }

            $room->status = $status;
            if ($status === 'available') {
                $room->company_id = null;
            }

            $room->save();

            return $this->success($room, 'Room status updated successfully', 200);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }

    // Remove a company from a room
    public function removeCompany(Request $request)
    {
        try {
            $validated = $request->validate([
                'room_id' => 'required|exists:rooms,id',
            ]);

            $room = Room::findOrFail($validated['room_id']);
            $room->update([
                'company_id' => null,
                'status'     => 'available',
            ]);

            return $this->success((object)[], 'Company removed from room.', 200);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }
}
