<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Collaborator;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CollaboratorController extends Controller
{
    use ApiResponse;

    // ======================
    // LIST
    // ======================
    public function index()
    {
        $authUser = Auth::guard('api')->user();

        if (!$authUser || !$authUser->company_id) {
            return $this->error([], 'Unauthorized', 401);
        }

        $collaborators = Collaborator::where('company_id', $authUser->company_id)
            ->latest()
            ->get()
            ->map(function ($collaborator) {
                return [
                    'id' => $collaborator->id,
                    'first_name' => $collaborator->first_name,
                    'last_name' => $collaborator->last_name,
                    'email' => $collaborator->email,
                    'phone_number' => $collaborator->phone_number,
                    'job_position' => $collaborator->job_position,
                    'access_card_number' => $collaborator->access_card_number,
                    'phone_extension' => $collaborator->phone_extension,
                    'parking_card' => (bool) $collaborator->parking_card,
                ];
            });

        return $this->success($collaborators, 'Collaborators fetched');
    }

    // ======================
    // STORE
    // ======================
    public function store(Request $request)
    {
        $authUser = Auth::guard('api')->user();

        if (!$authUser || !$authUser->company_id) {
            return $this->error([], 'Unauthorized', 401);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'nullable|email|unique:collaborators,email',
            'phone_number' => 'nullable|string',
            'job_position' => 'nullable|string|max:100',
            'access_card_number' => 'nullable|string|max:100',
            'phone_extension' => 'nullable|string|max:20',
            'parking_card' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $collaborator = Collaborator::create([
            'company_id' => $authUser->company_id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'job_position' => $request->job_position,
            'access_card_number' => $request->access_card_number,
            'phone_extension' => $request->phone_extension,
            'parking_card' => $request->parking_card ?? false,
        ]);

        return $this->success($collaborator, 'Collaborator created');
    }

    // ======================
    // UPDATE
    // ======================
    public function update(Request $request, $id)
    {
        $authUser = Auth::guard('api')->user();

        if (!$authUser || !$authUser->company_id) {
            return $this->error([], 'Unauthorized', 401);
        }

        $collaborator = Collaborator::where('company_id', $authUser->company_id)
            ->where('id', $id)
            ->first();

        if (!$collaborator) {
            return $this->error([], 'Collaborator not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'nullable|email|unique:collaborators,email,' . $id,
            'phone_number' => 'nullable|string',
            'job_position' => 'nullable|string|max:100',
            'access_card_number' => 'nullable|string|max:100',
            'phone_extension' => 'nullable|string|max:20',
            'parking_card' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $collaborator->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'job_position' => $request->job_position,
            'access_card_number' => $request->access_card_number,
            'phone_extension' => $request->phone_extension,
            'parking_card' => $request->parking_card ?? false,
        ]);

        return $this->success($collaborator, 'Collaborator updated');
    }

    // ======================
    // DELETE
    // ======================
    public function destroy($id)
    {
        $authUser = Auth::guard('api')->user();

        if (!$authUser || !$authUser->company_id) {
            return $this->error([], 'Unauthorized', 401);
        }

        $collaborator = Collaborator::where('company_id', $authUser->company_id)
            ->where('id', $id)
            ->first();

        if (!$collaborator) {
            return $this->error([], 'Collaborator not found', 404);
        }

        $collaborator->delete();

        return $this->success([], 'Collaborator removed successfully');
    }
}
