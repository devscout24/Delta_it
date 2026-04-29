<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class CompanyUserController extends Controller
{
    use ApiResponse;

    // ======================
    // LIST USERS (BY COMPANY)
    // ======================
    public function index(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id'
        ]);

        $users = User::where('company_id', $request->company_id)
            ->latest()
            ->paginate(10);

        $data = $users->getCollection()->map(function ($user) {
            return [
                'id' => $user->id,
                'first_name' => $user->name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'job_position' => $user->job_position,
            ];
        });

        return $this->success([
            'users' => $data,
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
            ]
        ], 'Users fetched');
    }

    // ======================
    // CREATE USER (ACCOUNT)
    // ======================
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',

            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',

            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',

            'job_position' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $user = User::create([
            'company_id' => $request->company_id,

            'name' => $request->first_name,
            'last_name' => $request->last_name,

            'email' => $request->email,
            'password' => Hash::make($request->password),

            'job_position' => $request->job_position,
        ]);

        return $this->success([
            'id' => $user->id,
            'email' => $user->email
        ], 'User created successfully', 201);
    }

    // ======================
    // SHOW USER
    // ======================
    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->error([], 'User not found', 404);
        }

        return $this->success($user, 'User details');
    }

    // ======================
    // UPDATE USER
    // ======================
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->error([], 'User not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'job_position' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $user->update([
            'name' => $request->first_name,
            'last_name' => $request->last_name,
            'job_position' => $request->job_position,
        ]);

        return $this->success($user, 'User updated');
    }

    // ======================
    // DELETE USER
    // ======================
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->error([], 'User not found', 404);
        }

        $user->delete();

        return $this->success([], 'User deleted');
    }
}
