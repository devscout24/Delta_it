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
    public function index()
    {
        $user = Auth::guard('api')->user();

        if (!$user || !$user->company_id) {
            return $this->error([], 'User not associated with any company', 403);
        }

        $collaborators = Collaborator::where('company_id', $user->company_id)
            ->select('id','company_id', 'first_name', 'last_name', 'job_position', 'email', 'phone_number', 'parking_card')
            ->get();

        if ($collaborators->isEmpty()) {
            return $this->error([], 'No collaborators found', 404);
        }

        return $this->success($collaborators, 'Collaborators fetched successfully', 200);
    }

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

    public function update(Request $request)
    {
        try {
            $user = Auth::guard('api')->user();

            if (!$user || !$user->company_id) {
                return $this->error([], 'User not associated with any company', 403);
            }

            $validator = Validator::make($request->all(), [
                'id'                 => 'required|integer|exists:collaborators,id',
                'first_name'         => 'required|string|max:255',
                'last_name'          => 'required|string|max:255',
                'job_position'       => 'nullable|string|max:255',
                'email'              => 'nullable|email|unique:collaborators,email,' . $request->id,
                'phone_extension'    => 'nullable|string|max:20',
                'phone_number'       => 'nullable|string|max:20',
                'access_card_number' => 'nullable|string|max:50',
                'parking_card'       => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors(), 'Validation Error', 422);
            }

            $validated = $validator->validated();

            $collaborator = Collaborator::where('id', $validated['id'])
                ->where('company_id', $user->company_id)
                ->first();

            if (!$collaborator) {
                return $this->error([], 'Collaborator not found', 404);
            }

            $collaborator->update([
                'first_name'         => $validated['first_name'],
                'last_name'          => $validated['last_name'],
                'job_position'       => $validated['job_position'] ?? null,
                'email'              => $validated['email'] ?? null,
                'phone_extension'    => $validated['phone_extension'] ?? null,
                'phone_number'       => $validated['phone_number'] ?? null,
                'access_card_number' => $validated['access_card_number'] ?? null,
                'parking_card'       => $validated['parking_card'] ?? false,
            ]);

            return $this->success([], 'Collaborator updated successfully', 200);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 'Server error', 500);
        }
    }

    public function destroy(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (!$user || !$user->company_id) {
            return $this->error([], 'User not associated with any company', 403);
        }

        $request->validate([
            'id' => 'required|integer|exists:collaborators,id',
        ]);

        $collaborator = Collaborator::where('id', $request->id)
            ->where('company_id', $user->company_id)
            ->first();

        if (!$collaborator) {
            return $this->error([], 'Collaborator not found', 404);
        }

        $collaborator->delete();

        return $this->success((object)[], 'Collaborator deleted successfully', 200);
    }
}
