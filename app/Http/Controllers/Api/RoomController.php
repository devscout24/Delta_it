<?php

namespace App\Http\Controllers\Api;

use App\Models\Room;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\RoomHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RoomController extends Controller
{
    use ApiResponse;

    public function stats()
    {
        $rooms = Room::all();

        $stats = [
            'available' => $rooms->where('status', 'available')->count(),
            'occupied' => $rooms->where('status', 'occupied')->count(),
            'maintenance' => $rooms->where('status', 'maintenance')->count(),
        ];

        return $this->success($stats, 'Stats fetched successfully', 200);
    }

    public function index(Request $request)
    {
        try {
            $query = Room::select('id', 'floor', 'room_name', 'area', 'polygon_points', 'status');

            if ($request->filled('floor')) {
                $query->where('floor', $request->floor);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status); // available, occupied or maintenance
            }

            $rooms = $query->get();

            return $this->success($rooms, 'Rooms fetched successfully', 200);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }


    public function addRoom(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'floor'           => 'required|string',
            'room_name'       => 'required|string|max:255',
            'area'            => 'required|numeric',
            'polygon_points'  => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        // store validated input
        $data = $validator->validated();

        DB::beginTransaction();
        try {
            // Check for uniqueness on same floor
            if (Room::where('floor', $data['floor'])
                ->where('room_name', $data['room_name'])
                ->exists()
            ) {
                return $this->error([], 'Room name already exists on this floor', 422);
            }


            // Convert flat array to coordinate pairs (added newly)
            $points = $data['polygon_points'];
            $converted = [];
            for ($i = 0; $i < count($points); $i += 2) {
                $converted[] = [
                    (float)$points[$i],
                    (float)$points[$i + 1]
                ];
            }
            // newly added end

            $room = Room::create([
                'floor'          => $data['floor'],
                'room_name'      => $data['room_name'],
                'area'           => $data['area'],
                'polygon_points' => json_encode($converted),
                'status'         => 'available',
            ]);

            DB::commit();

            return $this->success($room, 'Room added successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error([], $e->getMessage(), 500);
        }
    }


    // ================================================
    public function getCompanyInfo($id)
    {
        try {
            $room = Room::with([
                'company:id,name,incubation_type,manager,description',
                'company.contracts:id,company_id,start_date,end_date'
            ])
                ->select('id', 'room_name', 'status', 'company_id')
                ->find($id);

            if (!$room) {
                return $this->error([], 'Room not found.', 404);
            }

            // Prepare formatted response
            $response = [
                'room_name'       => $room->room_name,
                'company_name'    => $room->company->name ?? null,
                'status'          => $room->status,
                'incubation_type' => $room->company->incubation_type ?? null,
                'manager_name'    => $room->company->manager ?? null,
                'start_date'      => optional($room->company->contracts->first())->start_date,
                'end_date'        => optional($room->company->contracts->first())->end_date,
                'description'     => $room->company->description ?? null,
            ];

            return $this->success($response, 'Room details fetched successfully', 200);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }


    public function assignCompany(Request $request)
    {
        DB::beginTransaction();
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

            // ADD History
            $roomHistory = RoomHistory::create([
                'room_id' => $room->id,
                'company_id' => $validated['company_id'],
                'date' => now(),
                'status' => 'progress',
            ]);

            DB::commit();

            return $this->success($room->load('company'), 'Company assigned successfully', 200);
        } catch (\Exception $e) {
            DB::rollBack();
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
