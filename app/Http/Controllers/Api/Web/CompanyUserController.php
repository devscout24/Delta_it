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
    // LIST USERS
    // ======================
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $query = User::where('company_id', $request->company_id);

        // search (optional)
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('last_name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $users = $query->latest()->paginate(10);

        $data = $users->getCollection()->map(function ($user) {
            return [
                'id' => $user->id,
                'first_name' => $user->name,
                'last_name' => $user->last_name,
                'full_name' => $user->name . ' ' . $user->last_name,
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
    // CREATE USER
    // ======================
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',

            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',

            'email' => 'required|email|unique:users,email,NULL,id,company_id,' . $request->company_id,
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
    public function show(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $user = User::where('id', $id)
            ->where('company_id', $request->company_id)
            ->first();

        if (!$user) {
            return $this->error([], 'User not found', 404);
        }

        return $this->success([
            'id' => $user->id,
            'first_name' => $user->name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'job_position' => $user->job_position,
        ], 'User details');
    }

    // ======================
    // UPDATE USER
    // ======================
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $user = User::where('id', $id)
            ->where('company_id', $request->company_id)
            ->first();

        if (!$user) {
            return $this->error([], 'User not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'job_position' => 'nullable|string|max:100',

            'email' => 'required|email|unique:users,email,' . $id . ',id,company_id,' . $request->company_id,

            'password' => 'nullable|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $data = [
            'name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'job_position' => $request->job_position,
        ];

        // update password only if provided
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return $this->success([], 'User updated');
    }

    // ======================
    // DELETE USER
    // ======================
    public function destroy(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $user = User::where('id', $id)
            ->where('company_id', $request->company_id)
            ->first();

        if (!$user) {
            return $this->error([], 'User not found', 404);
        }

        $user->delete();

        return $this->success([], 'User deleted');
    }
}
