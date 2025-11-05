<?php

namespace App\Http\Controllers\Api;

use App\Models\CUser;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class CUserController extends Controller
{
    use ApiResponse;
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'company' => 'nullable',
            'email' => 'required|email|unique:c_users,email',
            'role' => 'required|in:Admin,Company,User',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = CUser::create([
            'name' => $request->name,
            'company' => $request->company,
            'email' => $request->email,
            'role' => $request->role,
            'password' => Hash::make($request->password),
        ]);

        return $this->success($user, 'User created successfully', 201);
    }


    /**
     *  Update User (with unique email + optional password update)
     */
    public function update(Request $request, $id)
    {
        $user = CUser::findOrFail($id);

        $request->validate([
            'name' => 'required',
            'company' => 'nullable',
            'email' => "required|email|unique:c_users,email,$id",
            'role' => 'required|in:Admin,Company,User',
            'password' => 'nullable|min:6|confirmed',
        ]);

        $user->name = $request->name;
        $user->company = $request->company;
        $user->email = $request->email;
        $user->role = $request->role;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return $this->success($user, 'User updated successfully', 200);
    }


    /**
     *  Delete User
     */
    public function destroy($id)
    {
        CUser::findOrFail($id)->delete();

        return $this->success(null, 'User deleted successfully', 200);
    }
}
