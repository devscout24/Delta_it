<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Models\Floor;
use App\Models\Room;
use App\Models\RoomAllocation;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RoomController extends Controller
{
    use ApiResponse;

    public function stats()
    {
        $rooms = Room::with(['activeAllocation'])->get();

        $stats = [
            'available' => $rooms->whereNull('activeAllocation')->count(),
            'occupied' => $rooms->whereNotNull('activeAllocation')->count(),
            'maintenance' => $rooms->where('status', 'maintenance')->count(),
        ];

        return $this->success($stats, 'Stats fetched');
    }

    public function index(Request $request)
    {
        $query = Room::with(['activeAllocation.company', 'floor']);

        if ($request->filled('floor_id')) {
            $query->where('floor_id', $request->floor_id);
        }

        $rooms = $query->get()->map(function ($room) {
            $allocation = $room->activeAllocation;

            return [
                'id' => $room->id,
                'floor_no' => $room->floor_no,
                'floor_id' => $room->floor_id,
                'room_name' => $room->room_name,
                'area' => $room->area,
                'polygon_points' => $room->polygon_points,
                'status' => $allocation ? 'occupied' : 'available',
                'company_id' => $allocation?->company_id,
                'company_name' => $allocation?->company?->name,
            ];
        });

        return $this->success($rooms, 'Rooms fetched');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'floor_id' => 'required|integer|exists:floors,id',
            'room_name' => 'required|string',
            'area' => 'required|numeric',
            'polygon_points' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        DB::beginTransaction();
        try {
            $points = $request->polygon_points;
            $converted = [];

            for ($i = 0; $i < count($points); $i += 2) {
                $converted[] = [(float) $points[$i], (float) $points[$i + 1]];
            }

            $room = Room::create([
                'floor_id' => $request->floor_id,
                'name' => $request->room_name,
                'area' => $request->area,
                'polygon_points' => json_encode($converted),
            ]);

            DB::commit();

            return $this->success($room->load('floor'), 'Room created', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error([], $e->getMessage(), 500);
        }
    }

    public function floors()
    {
        $floors = Floor::query()
            ->select(['id', 'name', 'level'])
            ->orderBy('level')
            ->get();

        return $this->success($floors, 'Floors fetched');
    }

    public function assignCompany(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'company_id' => 'required|exists:companies,id',
        ]);

        DB::beginTransaction();
        try {
            $room = Room::find($request->room_id);

            $exists = RoomAllocation::where('room_id', $room->id)
                ->where('status', 'active')
                ->exists();

            if ($exists) {
                return $this->error([], 'Room already occupied', 409);
            }

            RoomAllocation::create([
                'room_id' => $room->id,
                'company_id' => $request->company_id,
                'status' => 'active',
                'start_date' => now(),
            ]);

            DB::commit();

            return $this->success([], 'Company assigned successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error([], $e->getMessage(), 500);
        }
    }

    public function removeCompany(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
        ]);

        DB::beginTransaction();
        try {
            $allocation = RoomAllocation::where('room_id', $request->room_id)
                ->where('status', 'active')
                ->first();

            if (!$allocation) {
                return $this->error([], 'No company assigned');
            }

            $allocation->update([
                'status' => 'ended',
                'end_date' => now(),
            ]);

            DB::commit();

            return $this->success([], 'Company removed');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error([], $e->getMessage(), 500);
        }
    }

    public function details($id)
    {
        $room = Room::with([
            'activeAllocation.company.contracts',
            'floor',
        ])->find($id);

        if (!$room) {
            return $this->error([], 'Room not found', 404);
        }

        $allocation = $room->activeAllocation;
        $company = $allocation?->company;

        return $this->success([
            'floor_id' => $room->floor_id,
            'floor_no' => $room->floor_no,
            'room_name' => $room->room_name,
            'status' => $allocation ? 'occupied' : 'available',
            'company_name' => $company?->name,
            'incubation_type' => $company?->incubation_type,
            'manager_name' => $company?->manager_name,
            'start_date' => optional($company?->contracts->first())->start_date,
            'end_date' => optional($company?->contracts->first())->end_date,
            'description' => $company?->description,
        ], 'Room details fetched');
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:maintenance,available',
        ]);

        $room = Room::find($id);

        if (!$room) {
            return $this->error([], 'Room not found', 404);
        }

        return $this->success([], 'Status updated (logic placeholder)');
    }
}
