<?php

namespace App\Http\Controllers\Api;


use App\Models\User;
use App\Mail\OtpSend;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use App\Models\UserPreference;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Carbon\Carbon;
use Illuminate\Support\Str;


class AuthController extends Controller
{
    use ApiResponse;

    // ================
    // Auth Methods
    // ================

    public function signup(Request $request)
    {
        $validator = Validator::make($request->all(),
            [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email',
                'password' => 'required|string|min:6|confirmed',
            ],
            [
                'name.required' => 'Name is required',
                'email.required' => 'Email is required',
                'email.email' => 'Email must be a valid email address',
                'email.unique' => 'Email already exists',
                'password.required' => 'Password is required',
                'password.min' => 'Password must be at least 6 characters',
                'password.confirmed' => 'Password and confirmation password do not match',
            ]
        );

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        // Create the user
        $user = User::create([
            'name'          => $request->name,
            'email'         => $request->email,
            'password'      => bcrypt($request->password),
            'last_login_at' => now()
        ]);

        // Attempt token-based login (JWT)
        $credentials = $request->only('email', 'password');
        $token = Auth::guard('api')->attempt($credentials);

        if (!$token) {
            return $this->error([], 'Registration successful but token generation failed.', 500);
        }

        // Format user data
        $userData = [
            'id'     => $user->id,
            'token'  => $token,
            'name'   => $user->name,
            'email'  => $user->email,
            'avatar' => asset($user->avatar == null ? asset('user.png') : asset($user->avatar)),
        ];

        return $this->success($userData, 'Registration and login successful', 200);
    }

    public function login(Request $request)
    {
        // Validate request inputs
        $validate = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validate->fails()) {
            return $this->error($validate->errors(), $validate->errors()->first(), 422);
        }

        // Attempt login with email and password
        $credentials = $request->only('email', 'password');
        $token = Auth::guard('api')->attempt($credentials);

        // Return error if credentials are invalid
        if (!$token) {
            return $this->error([], 'Hmm, that didn’t work. Double-check your login details', 401);
        }

        // Retrieve authenticated user
        $user = Auth::guard('api')->user();

        // Update last login timestamp
        $user->last_login_at = now();
        $user->save();

        // Format user data
        $userData = [
            'id'            => $user->id,
            'token'         => $token,
            'name'          => $user->name == null ? '' : $user->name,
            'last_name'     => $user->last_name == null ? '' : $user->last_name,
            'email'         => $user->email,
            'username'      => $user->username,
            'avatar' => asset($user->avatar == null ? 'user.png' : $user->avatar),
        ];


        return $this->success($userData, 'Login Successful', 200);
    }


    public function logout()
    {
        try {
            // Get token from request
            $token = JWTAuth::getToken();

            if (!$token) {
                return $this->error([], 'Token not provided', 401);
            }

            // Invalidate token
            JWTAuth::invalidate($token);

            return $this->success([], 'Successfully logged out', 200);
        } catch (JWTException $e) {
            return $this->error([], 'Failed to logout. ' . $e->getMessage(), 500);
        }
    }

    // ================
    // User Preference Methods
    // ================

    public function getUserPreferences(Request $request)
    {
        $user = Auth::guard('api')->user();

        $preferences = $user->preference ?? null;

        if (!$preferences) {
            return $this->error([], 'User preferences not set.', 200);
        }

        $data  = [
            'travel_distance'   => $preferences->travel_distance,
            'preferred_weather' => $preferences->preferred_weather,
            'companion_type'    => $preferences->companion_type,
            'spending_comfort'  => $preferences->spending_comfort,
            'preferred_time'    => $preferences->preferred_time,
        ];

        return $this->success($data, 'User preferences retrieved successfully.', 200);
    }

    public function setUserPreferences(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'travel_distance'   => 'nullable|in:nearby,within_city,long_trip,doesnt_matter',
            'preferred_weather' => 'nullable|in:sunny,rainy,winter,doesnt_matter',
            'companion_type'    => 'nullable|in:alone,friends,family,partner',
            'spending_comfort'  => 'nullable|in:low,medium,premium,doesnt_matter',
            'preferred_time'    => 'nullable|in:morning,afternoon,evening,night',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        $user = Auth::guard('api')->user();

        if (!$user) {
            return $this->error([], 'Unauthenticated user.', 401);
        }

        $preferences = $user->preference;

        $data = $validator->validated();

        if ($preferences) {
            // Update existing preferences
            $preferences->update($data);
        } else {
            // Create new preferences
            $data['user_id'] = $user->id;
            $preferences = UserPreference::create($data);
        }

        return $this->success($preferences, 'User preferences saved successfully.', 200);
    }


    // OTP Methods
    // ================

    public function sendOtp(Request $request)
    {
        // Validate incoming email
        $request->validate([
            'email' => 'required|email',
        ]);

        // Check if user exists
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->error([], 'User with this email does not exist.', 404);
        }

        // Generate OTP and expiry
        $otp = rand(1000, 9999);
        $expiresAt = Carbon::now()->addMinutes(15);

        // Save OTP and expiry to user
        $user->update([
            'otp' => $otp,
            'otp_expired_at' => $expiresAt,
        ]);

        // Send OTP via email
        Mail::to($user->email)->send(new OtpSend($otp));

        // Return success response (without exposing OTP in production)
        return $this->success([
            'email' => $user->email,
            'expires_at' => $expiresAt,
            // 'otp' => $otp // ⚠️ Only return for testing/debug — remove in prod
        ], 'OTP sent successfully.', 200);
    }

    public function verifyOtp(Request $request)
    {

        $request->validate([
            'otp' => 'required',
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->where('otp', $request->otp)->first();

        if (!$user) {
            return $this->error([], 'Invalid otp', 400);
        } else if ($user->otp_expired_at < Carbon::now()) {

            $user->otp = null;
            $user->otp_expired_at = null;
            $user->save();

            return $this->error([], 'OTP expired', 400);
        }

        $user->otp_verified_at                 = Carbon::now();
        $user->password_reset_token            = Str::random(64);
        $user->password_reset_token_expires_at = Carbon::now()->addMinutes(15);
        $user->save();

        return $this->success([
            'email' => $user->email,
            'reset_token' => $user->password_reset_token,
        ], 'OTP verified successfully', 200);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'       => 'required|email',
            'password'    => 'required|string|min:6|confirmed',
            'reset_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Error in Validation', 422);
        }

        $user = User::where('email', $request->email)
            ->where('password_reset_token', $request->reset_token)
            ->first();

        if (!$user) {
            return $this->error([], 'Invalid token or email.', 400);
        }

        if ($user->password_reset_token_expires_at < Carbon::now()) {
            return $this->error([], 'Token expired.', 400);
        }

        // ✅ Save new password first
        $user->password = bcrypt($request->password);
        $user->password_reset_token = null;
        $user->password_reset_token_expires_at = null;
        $user->save();

        // ✅ Attempt login after saving new password
        $credentials = $request->only('email', 'password');
        if (!$token = Auth::guard('api')->attempt($credentials)) {
            return $this->error([], 'Unable to login. Please try again.', 401);
        }

        // ✅ Prepare user data
        $userData = [
            'id'       => $user->id,
            'token'    => $token,
            'name'     => $user->name ?? 'User_name_' . uniqid(),
            'email'    => $user->email,
            'username' => $user->username,
            'avatar'   => asset($user->avatar ?? 'user.png'),
        ];

        return $this->success($userData, 'Password reset & login successful', 200);
    }

    // ================
    // Store FCM Token
    // ================

    public function storeFcmToken(Request $request)
    {
        // dd($request->all());
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Error in Validation', 422);
        }

        $user = Auth::guard('api')->user();

        // Check if device exists
        $existing = $user->fcmTokens()->where('device_id', $request->device_id)->first();

        if ($existing) {
            $existing->update(['token' => $request->token]);
        } else {
            $user->fcmTokens()->create([
                'device_id' => $request->device_id,
                'token' => $request->token,
            ]);
        }

        $response = [
            'device_id' => $user->fcmTokens()->where('device_id', $request->device_id)->first()->device_id,
            'token' =>  $user->fcmTokens()->where('device_id', $request->device_id)->first()->token,
        ];

        return $this->success($response, 'FCM token stored successfully', 200);
    }

    public function deleteFcmToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Error in Validation', 422);
        }

        $user = Auth::guard('api')->user();

        $user->fcmTokens()->where('device_id', $request->device_id)->delete();

        return $this->success([], 'FCM token deleted successfully', 200);
    }


    // user profile


    public function deleteSelfAccount()
    {
        $user = Auth::guard('api')->user();
        $user = User::find($user->id);
        $user->delete();
        return $this->success((object)[], 'Your account has been deactivated. You can reactivate later.', 200);
    }

    public function userResetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation Error', 422);
        }

        $user = Auth::guard('api')->user();


        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error(['current_password' => ['Current password is incorrect']], 'Authentication Failed', 401);
        }

        $userModel = User::find($user->id);
        $userModel->password = Hash::make($request->new_password);
        $userModel->save();
        return $this->success((object)[], 'Password changed successfully', 200);
    }

    // company account create
    public function createAccount(Request $request)
    {
        $validator = validator(
            $request->all(),
            [
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:8',
            ],
            [

                'email.required' => 'Email is required.',
                'email.email' => 'Please provide a valid email address.',
                'email.unique' => 'This email is already registered.',
                'password.required' => 'Password is required.',
                'password.min' => 'Password must be at least 8 characters.',
            ]
        );

        // Check for validation errors
        if ($validator->fails()) {
            return $this->error(null, $validator->errors(), 422);
        }

        // If validation passes Create user
        $user = User::create([
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'user_type' => 'user'
        ]);


        return $this->success($user, 'Account created Successfully', 200);
    }
}
