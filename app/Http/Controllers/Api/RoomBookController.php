<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RoomBookings;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class RoomBookController extends Controller
{
    use ApiResponse;
    public function RoomBook(Request $request)
    {
        $validatedData = $request->validate([
            'booking_name' => 'required|string|max:255',
            'company_id'   => 'required|integer|exists:companies,id',
            'room_id'      => 'required|integer|exists:rooms,id',
            'start_date'   => 'required|date|before_or_equal:end_date',
            'end_date'     => 'required|date|after_or_equal:start_date',
            'add_emails'   => 'required|array|min:1',
            'add_emails.*' => 'email',
        ]);

        $bookData = RoomBookings::create($validatedData);

        if (! $bookData) {
            return $this->error(null, 'Room booking not created', 500);
        }

        return $this->success($bookData, 'Room booking created successfully', 201);
    }
}
