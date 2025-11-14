<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    use ApiResponse;
    public function get(Request $request)
    {
        // Auth user has company_id
        $companyId = $request->company_id;

        $users = User::select(
            'id',
            'company_id',
            'job_position',
            'profile_photo',
            'username',
            'name',
            'last_name',
            'email',
            'phone',
            'user_type',
            'status'
        )
        ->where('company_id', $companyId)
        ->get()
        ->map(function ($user) {
            $user->profile_photo = $user->profile_photo
                ? asset($user->profile_photo)
                : asset('default/avatar.png');
            return $user;
        });

        return $this->success($users, "Company users fetched successfully", 200);
    }

    /**
     * ADD COMPANY USER
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'last_name'   => 'nullable|string|max:100',
            'job_position'=> 'nullable|string|max:255',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|min:6|confirmed'
        ]);

        $user = new User();
        $user->company_id   = $request->company_id;
        $user->name         = $request->name;
        $user->last_name    = $request->last_name;
        $user->job_position = $request->job_position;
        $user->email        = $request->email;
        $user->password     = Hash::make($request->password);
        $user->user_type    = "company_user";
        $user->status       = "active";

        $user->save();

        return $this->success($user, "Company user created successfully", 201);
    }

    /**
     * UPDATE COMPANY USER
     */
    public function update(Request $request)
    {
        $request->validate([
            'id'          => 'required|exists:users,id',
            'name'        => 'required|string|max:100',
            'last_name'   => 'nullable|string|max:100',
            'job_position'=> 'nullable|string|max:255',
            'email'       => ['required','email', Rule::unique('users')->ignore($request->id)],
            'password'    => 'nullable|min:6|confirmed'
        ]);

        $user = User::where('company_id', $request->company_id)
                    ->where('id', $request->id)
                    ->first();

        if (!$user) {
            return $this->error(null, "User not found", 404);
        }

        $user->name         = $request->name;
        $user->last_name    = $request->last_name;
        $user->job_position = $request->job_position;
        $user->email        = $request->email;

        if ($request->password) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return $this->success($user, "Account updated successfully", 200);
    }

    /**
     * DELETE COMPANY USER
     */
    public function destroy($id)
    {
        $user = User::where('id', $id)
                    ->first();

        if (!$user) {
            return $this->error(null, "User not found", 404);
        }

        $user->delete();

        return $this->success(null, "Company user deleted successfully", 200);
    }
}
