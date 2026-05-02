<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use App\Models\User;

class UserManagementController extends Controller
{
    use ApiResponse;

    // ======================
    // LIST USERS
    // ======================
    public function index(Request $request)
    {
        $query = User::with('company')->latest();

        // filters
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // 🔍 search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $users = $query->paginate(10);

        $data = $users->getCollection()->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role,
                'company' => $u->company?->name,
                'company_id' => $u->company_id,
            ];
        });

        return $this->success([
            'data' => $data,
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
            ],
            'stats' => [ // 🔥 optional but useful
                'total_admins' => User::where('role', 'admin')->count(),
                'total_company_users' => User::where('role', 'company')->count(),
            ]
        ], 'Users fetched');
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

        // 🚨 enforce logic
        if ($data['role'] === 'admin') {
            $data['company_id'] = null;
        }

        if ($data['role'] === 'company' && empty($data['company_id'])) {
            return $this->error([], 'Company user must have company_id', 422);
        }

        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        return $this->success($user, 'User created', 201);
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

        // role logic
        if (isset($data['role'])) {
            if ($data['role'] === 'admin') {
                $data['company_id'] = null;
            }

            if ($data['role'] === 'company' && empty($data['company_id'])) {
                return $this->error([], 'Company user must have company_id', 422);
            }
        }

        // password
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

        // 🚨 correct self-check
        if ($user->id === Auth::id()) {
            return $this->error([], 'You cannot delete yourself', 422);
        }

        $user->delete();

        return $this->success([], 'User deleted');
    }
}
