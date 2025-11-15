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
            return $this->success($rooms, 'Rooms fetched successfully', 200);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }

    public function addRoom(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'floor'           => 'required|integer',
            'room_name'       => 'required|string|max:255',
            'area'            => 'required|numeric',
            'polygon_points'  => 'required|array',
        ]);

        if ($validate->fails()) {
            return $this->error($validate->errors(), $validate->errors()->first(), 422);
        }

        try {
            // Check for uniqueness on the same floor
            if (Room::where('floor', $validate->validated()['floor'])
                ->where('room_name', $validate->validated()['room_name'])
                ->exists()
            ) {
                return $this->error([], 'Room name already exists on this floor', 422);
            }

            $room = Room::create([
                'floor'           => $validate['floor'],
                'room_name'       => $validate['room_name'],
                'area'            => $validate['area'],
                'polygon_points'  => json_encode($validate['polygon_points']),
                'status'          => 'available',
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
