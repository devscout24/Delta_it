<?php

namespace App\Http\Controllers\Api;

use App\Models\Room;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RoomController extends Controller
{
    use ApiResponse;

    // Get all room data for the map
    public function index()
    {
        try {
            $rooms = Room::with('company')->get();
            $rooms->each(function ($room) {
                if ($room->company && $room->company->logo) {
                    $room->company->logo = asset($room->company->logo);
                }
            });

            return $this->success($rooms, 'Rooms fetched successfully', 200);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }

    public function addRoom(Request $request)
    {
        try {
            $validated = $request->validate([
                'room_name'      => 'required|string|max:255',
                'area'           => 'required|numeric',
                'polygon_points' => 'required|array',
                'company_id'     => 'nullable|exists:companies,id',
            ]);

            if (Room::where('room_name', $validated['room_name'])->exists()) {
                return $this->error([], 'Room name already exists', 422);
            }

            if (Room::where('area', $validated['area'])->exists()) {
                return $this->error([], 'Area already exists', 422);
            }

            $room = Room::create([
                'room_name'      => $validated['room_name'],
                'area'           => $validated['area'],
                'polygon_points' => json_encode($validated['polygon_points']),
                'company_id'     => $validated['company_id'] ?? null,
                'status'         => 'available',
            ]);

            return $this->success($room, 'Room added successfully', 201);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }

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
