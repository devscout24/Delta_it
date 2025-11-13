<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class ProfileController extends Controller
{
    use \App\Traits\ApiResponse;
    public function getUserProfile()
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return $this->error('', 'User not found', 404);
        }

        return $this->success($user, 'User profile retrieved successfully', 200);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return $this->error('', 'User not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'zipcode' => 'nullable|string|max:20',
            'profile_photo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        // Handle profile photo upload
        if ($request->hasFile('profile_photo')) {
            $file = $request->file('profile_photo');
            $uploadPath = $this->uploadImage($file, $user->profile_photo, 'uploads/profile_photos', 200, 200);
        } else {
            $uploadPath = $user->profile_photo;
        }

        // Update user profile
        $user->update([
            'name' => $request->name,
            'last_name' => $request->last_name,
            'phone' => $request->phone,
            'address' => $request->address,
            'zipcode' => $request->zipcode,
            'profile_photo' => $uploadPath,
        ]);

        return $this->success($user, 'Profile updated successfully', 200);
    }

    public function changePassword(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return $this->error('', 'User not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('', 'Current password is incorrect', 401);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return $this->success([], 'Password changed successfully', 200);
    }
}
