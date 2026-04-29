<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Company;

class ProfileController extends Controller
{
    use \App\Traits\ApiResponse;

    // ======================
    // GET PROFILE
    // ======================
    public function getUserProfile()
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return $this->error([], 'User not found', 404);
        }

        $company = $user->company_id
            ? Company::find($user->company_id)
            : null;

        return $this->success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'job_title' => $user->job_title,
            'role' => $user->role,
            'company_id' => $user->company_id,
            'company_name' => $company?->name,
        ], 'Profile fetched successfully');
    }

    // ======================
    // UPDATE PROFILE
    // ======================
    public function updateProfile(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return $this->error([], 'User not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'job_title' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $user->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'job_title' => $request->job_title,
        ]);

        return $this->success($user, 'Profile updated successfully');
    }

    // ======================
    // CHANGE PASSWORD
    // ======================
    public function changePassword(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return $this->error([], 'User not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error([], 'Current password is incorrect', 401);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return $this->success([], 'Password changed successfully');
    }

    // ======================
    // DELETE ACCOUNT
    // ======================
    public function deleteAccount()
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return $this->error([], 'User not found', 404);
        }

        $user->delete();

        return $this->success([], 'Account deleted successfully');
    }
}
