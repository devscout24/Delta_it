<?php

namespace App\Http\Controllers\Api;

use App\Models\Room;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RoomController extends Controller
{
    use ApiResponse;
    public function addRoom(Request $request)
    {
        $validated = $request->validate([
            'room_name' => 'required',
            'area' => 'required',
            'position' => 'nullable',
        ]);

        if (Room::where('room_name', $validated['room_name'])->exists()) {
            return $this->error(null, 'Room name already exists', 422);
        }

        if (Room::where('area', $validated['area'])->exists()) {
            return $this->error(null, 'Area already exists', 422);
        }

        $room = Room::create($validated);
        return $this->success($room, 'Room added successfully', 201);
    }
}
