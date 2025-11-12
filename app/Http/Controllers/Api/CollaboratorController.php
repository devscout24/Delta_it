<?php

namespace App\Http\Controllers\Api;

use App\Traits\ApiResponse;
use App\Models\Collaborator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CollaboratorController extends Controller
{
    use ApiResponse;
    // Show list
    public function index()
    {
        $collaborators = Collaborator::all();
        if ($collaborators->isEmpty()) {
            return $this->error('', 'No collaborator found', 404);
        }
        return $this->success($collaborators, 'Collaborators fetched successful', 200);
    }

    // Store data
    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'job_position' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:collaborators,email',
            'phone_extension' => 'nullable|string|max:20',
            'phone_number' => 'nullable|string|max:20',
            'access_card_number' => 'nullable|string|max:50',
            'parking_card' => 'nullable|boolean',
        ]);

        if ($validate->fails()) {
            return $this->error($validate->errors(), 'Validation Error', 422);
        }

        try {
            $user = Auth::guard('api')->user();

            if (!$user || !$user->company_id) {
                return $this->error('', 'User not associated with any company', 403);
            }

            Collaborator::create([
                'company_id'         => $user->company_id,
                'first_name'         => $request->first_name,
                'last_name'          => $request->last_name,
                'job_position'       => $request->job_position,
                'email'              => $request->email,
                'phone_extension'    => $request->phone_extension,
                'phone_number'       => $request->phone_number,
                'access_card_number' => $request->access_card_number,
                'parking_card'       => $request->parking_card ?? false,
            ]);

            return $this->success([], 'Collaborator added successfully', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 'Server error', 500);
        }
    }


    // Update data
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name'  => 'required|string|max:255',
                'job_position' => 'nullable|string|max:255',
                'email' => 'nullable|email',
                'phone_extension' => 'nullable|string|max:20',
                'phone_number' => 'nullable|string|max:20',
                'access_card_number' => 'nullable|string|max:50',
            ]);

            $validated['parking_card'] = $request->has('parking_card');

            $collaborator = Collaborator::find($request->id)->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'job_position' => $request->job_position  ?? null,
                'email' => $request->email ?? null,
                'phone_extension' => $request->phone_extension ?? null,
                'phone_number' => $request->phone_number ?? null,
                'access_card_number' => $request->access_card_number ?? null,
                'parking_card' => $request->parking_card,
            ]);
            return $this->success((object)[], 'Collaborator Updated Successful');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 'Error in server', 500);
        }
    }

    // Delete
    public function destroy(Request $request)
    {
        $collaborator  =  Collaborator::find($request->id);
        if (!$collaborator) {
            return $this->error('', 'No collaborator found', 404);
        }
        $collaborator->delete();
        return $this->success((object)[], 'Collaborator Deleted Successful');
    }
}
