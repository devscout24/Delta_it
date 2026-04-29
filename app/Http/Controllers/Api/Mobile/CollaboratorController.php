<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CollaboratorController extends Controller
{
    use ApiResponse;

    // ======================
    // LIST
    // ======================
    public function index()
    {
        $user = Auth::guard('api')->user();

        if (!$user || !$user->company_id) {
            return $this->error([], 'Unauthorized', 401);
        }

        $users = User::where('company_id', $user->company_id)
            ->where('role', 'company_user')
            ->select('id', 'name', 'email', 'phone', 'job_title')
            ->latest()
            ->get();

        return $this->success($users, 'Collaborators fetched');
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
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string',
            'job_title' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make('password123'), // default
            'role' => 'company_user',
            'company_id' => $authUser->company_id,
            'phone' => $request->phone,
            'job_title' => $request->job_title,
            'status' => 'active',
        ]);

        return $this->success($user, 'Collaborator created');
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

        $user = User::where('company_id', $authUser->company_id)
            ->where('id', $id)
            ->first();

        if (!$user) {
            return $this->error([], 'User not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'nullable|string',
            'job_title' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $user->update($request->only([
            'name',
            'email',
            'phone',
            'job_title',
        ]));

        return $this->success($user, 'Collaborator updated');
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

        $user = User::where('company_id', $authUser->company_id)
            ->where('id', $id)
            ->first();

        if (!$user) {
            return $this->error([], 'User not found', 404);
        }

        $user->delete();

        return $this->success([], 'Collaborator deleted');
    }
}
