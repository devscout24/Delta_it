<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpSend;

class AuthController extends Controller
{
    use ApiResponse;

    // ======================
    // LOGIN HANDLER
    // ======================
    private function loginForRole(Request $request, array $allowedRoles, string $successMsg, string $forbiddenMsg)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->error([], 'Invalid email or password', 401);
        }

        if (!in_array($user->role, $allowedRoles)) {
            return $this->error([], $forbiddenMsg, 403);
        }

        if ($user->status !== 'active') {
            return $this->error([], 'Account is inactive', 403);
        }

        if (!$token = Auth::guard('api')->attempt($request->only('email', 'password'))) {
            return $this->error([], 'Invalid email or password', 401);
        }

        $user->last_login_at = now();
        $user->save();

        return $this->success([
            'id' => $user->id,
            'token' => $token,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'company_id' => $user->company_id,
        ], $successMsg, 200);
    }

    // ======================
    // LOGIN ENDPOINTS
    // ======================

    public function login(Request $request)
    {
        return $this->loginForRole(
            $request,
            ['company_user'],
            'Login successful',
            'This account is not allowed to access mobile app'
        );
    }

    public function adminLogin(Request $request)
    {
        return $this->loginForRole(
            $request,
            ['admin'],
            'Admin login successful',
            'This account is not allowed to access admin panel'
        );
    }

    // ======================
    // LOGOUT
    // ======================

    public function logout()
    {
        try {
            $token = JWTAuth::getToken();

            if (!$token) {
                return $this->error([], 'Token not provided', 401);
            }

            JWTAuth::invalidate($token);

            return $this->success([], 'Logged out successfully', 200);
        } catch (JWTException $e) {
            return $this->error([], 'Logout failed', 500);
        }
    }

    // ======================
    // FORGOT PASSWORD (OTP)
    // ======================

    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->error([], 'User not found', 404);
        }

        $otp = rand(1000, 9999);
        $expiresAt = Carbon::now()->addMinutes(10);

        $user->update([
            'otp' => $otp,
            'otp_expired_at' => $expiresAt,
        ]);

        Mail::to($user->email)->send(new OtpSend($otp));

        return $this->success([
            'email' => $user->email,
            'expires_at' => $expiresAt,
        ], 'OTP sent successfully');
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required',
        ]);

        $user = User::where('email', $request->email)
            ->where('otp', $request->otp)
            ->first();

        if (!$user) {
            return $this->error([], 'Invalid OTP', 400);
        }

        if ($user->otp_expired_at < now()) {
            return $this->error([], 'OTP expired', 400);
        }

        $user->update([
            'password_reset_token' => Str::random(60),
            'password_reset_token_expires_at' => now()->addMinutes(15),
        ]);

        return $this->success([
            'reset_token' => $user->password_reset_token,
        ], 'OTP verified');
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'reset_token' => 'required',
            'password' => 'required|confirmed|min:6',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $user = User::where('email', $request->email)
            ->where('password_reset_token', $request->reset_token)
            ->first();

        if (!$user) {
            return $this->error([], 'Invalid token', 400);
        }

        if ($user->password_reset_token_expires_at < now()) {
            return $this->error([], 'Token expired', 400);
        }

        $user->update([
            'password' => bcrypt($request->password),
            'password_reset_token' => null,
            'password_reset_token_expires_at' => null,
        ]);

        return $this->success([], 'Password reset successful');
    }

    // ======================
    // PROFILE
    // ======================

    public function updateUser(Request $request)
    {
        $user = Auth::guard('api')->user();

        $user->update($request->only([
            'name',
            'phone',
            'job_title',
        ]));

        return $this->success($user, 'Profile updated');
    }

    public function deleteSelfAccount()
    {
        $user = Auth::guard('api')->user();
        $user->delete();

        return $this->success([], 'Account deleted');
    }
}
