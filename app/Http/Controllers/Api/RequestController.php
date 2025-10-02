<?php

namespace App\Http\Controllers\Api;

use App\Models\UserRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Validator;

class RequestController extends Controller
{
    use ApiResponse;
    public function store(Request $request)
    {
        // Validate the request data
        $validator  = Validator::make($request->all(), [
            'description'   => 'required|string',
            'date'          => 'required|date',
            'requested_by'  => 'required|string|max:100',
            'status'        => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create new request record
        UserRequest::create([
            'description'   => $request->description,
            'date'          => $request->date,
            'requested_by'  => $request->requested_by,
            'status'        => $request->status ?? 'pending'
        ]);

        return $this->success(null, 'Request submitted successfully', 201);
    }


    public function update(Request $request,)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'description'   => 'required|string',
            'date'          => 'required|date',
            'requested_by'  => 'required|string|max:100',
            'status'        => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Find the existing request
        $userRequest = UserRequest::find($request->id);
        if (!$userRequest) {
            return response()->json(['message' => 'Request not found'], 404);
        }

        // Update record
        $userRequest->update([
            'description'   => $request->description,
            'date'          => $request->date,
            'requested_by'  => $request->requested_by,
            'status'        => $request->status ?? $userRequest->status,
        ]);

        return $this->success($userRequest, 'Request updated successfully');
    }

    public function  show($id)
    {
        return $this->success(UserRequest::all(), 'Request fetched successfully');
    }
}
