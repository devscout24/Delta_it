<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    use ApiResponse;

    /**
     * GET: list company users
     * Expects: company_id (query param)
     */
    public function index(Request $request)
    {
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
        ->where('company_id', '!=', null)
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
     * POST: create company user
     */
    public function create(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'company_id'   => 'required|exists:companies,id',
            'name'         => 'required|string|max:100',
            'last_name'    => 'nullable|string|max:100',
            'job_position' => 'nullable|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'password'     => 'required|min:6|confirmed'
        ]);

        if ($validation->fails()) {
            return $this->error([], $validation->errors(), 422);
        }

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
}
