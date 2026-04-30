<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserManagementController extends Controller
{
    use ApiResponse;

    // ======================
    // LIST USERS
    // ======================
    public function index(Request $request)
    {
        $query = User::with('company')->latest();

        // optional filters
        if ($request->role) {
            $query->where('role', $request->role);
        }

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        $users = $query->get()->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role,
                'company' => $u->company?->name,
                'company_id' => $u->company_id,
            ];
        });

        return $this->success($users, 'Users fetched');
    }

    // ======================
    // CREATE USER
    // ======================
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,company',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $data = $validator->validated();

        // 🚨 RULE: admin must NOT have company
        if ($data['role'] === 'admin') {
            $data['company_id'] = null;
        }

        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        return $this->success($user, 'User created');
    }

    // ======================
    // SHOW USER
    // ======================
    public function show($id)
    {
        $user = User::with('company')->find($id);

        if (!$user) {
            return $this->error([], 'User not found', 404);
        }

        return $this->success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'company_id' => $user->company_id,
            'company_name' => $user->company?->name,
        ], 'User details');
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
            'name' => 'nullable|string|max:100',
            'email' => 'nullable|email|unique:users,email,' . $id,
            'password' => 'nullable|min:6',
            'role' => 'nullable|in:admin,company',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $data = $validator->validated();

        // 🚨 enforce role logic
        if (isset($data['role']) && $data['role'] === 'admin') {
            $data['company_id'] = null;
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

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

        // 🚨 prevent deleting self (optional safety)
        if ($user->id === Auth::user('api')->id) {
            return $this->error([], 'You cannot delete yourself', 422);
        }

        $user->delete();

        return $this->success([], 'User deleted');
    }
}
