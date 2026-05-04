<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    use ApiResponse;

    // ======================
    // LIST ROOMS
    // ======================
    public function index(Request $request)
    {
        $query = Room::with(['activeAllocation.company', 'floor']);

        if ($request->filled('floor_id')) {
            $query->where('floor_id', $request->floor_id);
        }

        $rooms = $query->get()->map(function ($room) {
            $allocation = $room->activeAllocation;

            $status = $room->status === 'maintenance'
                ? 'maintenance'
                : ($allocation ? 'occupied' : 'available');

            return [
                'id'           => $room->id,
                'floor_id'     => $room->floor_id,
                'floor_no'     => $room->floor?->level,
                'room_name'    => $room->name,
                'area'         => $room->area,
                'status'       => $status,
                'company_id'   => $allocation?->company_id,
                'company_name' => $allocation?->company?->name,
            ];
        });

        return $this->success($rooms, 'Rooms fetched');
    }

    // ======================
    // ROOM DETAILS
    // ======================
    public function details($id)
    {
        $room = Room::with(['activeAllocation.company.contracts', 'floor'])->find($id);

        if (!$room) {
            return $this->error([], 'Room not found', 404);
        }

        $allocation = $room->activeAllocation;
        $company    = $allocation?->company;

        $status = $room->status === 'maintenance'
            ? 'maintenance'
            : ($allocation ? 'occupied' : 'available');

        return $this->success([
            'id'              => $room->id,
            'floor_id'        => $room->floor_id,
            'floor_no'        => $room->floor?->level,
            'room_name'       => $room->name,
            'area'            => $room->area,
            'status'          => $status,
            'company_id'      => $company?->id,
            'company_name'    => $company?->name,
            'incubation_type' => $company?->incubation_type,
            'manager_name'    => $company?->manager_name,
            'start_date'      => optional($company?->contracts->first())->start_date,
            'end_date'        => optional($company?->contracts->first())->end_date,
            'description'     => $company?->description,
        ], 'Room details fetched');
    }
}
