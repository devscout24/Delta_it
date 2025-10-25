<?php

namespace App\Http\Controllers\Api;


use Carbon\Carbon;
use App\Models\User;
use App\Mail\OtpSend;
use App\Traits\ApiResponse;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use App\Models\ShippingAddress;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    use ApiResponse;

    //user login and logout
    public function login(Request $request)
    {


        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6|max:8'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        //  get all user include (soft delete)
        $user = User::withTrashed()->where('email', $request->email)->first();
        if (!$user) {
            return $this->error((object) [], 'No user account found for the provided email.', 404);
        }

        //  Restore user if soft-deleted
        if ($user->trashed()) {
            $user->restore();
        }

        //  Verify password
        if (!Hash::check($request->password, $user->password)) {
            return $this->error((object) [], 'Invalid entered password', 401);
        }

        //  Attempt login & generate token
        $token = Auth::guard('api')->attempt($request->only('email', 'password'));
        if (!$token) {
            return $this->error((object) [], 'Unauthorized User', 401);
        }

        //  Update last login time
        $user->update(['last_login_at' => now()]);

        //  Prepare response data
        $userData = [
            'id'            => $user->id,
            'email'         => $user->email,
            'profile_photo' => asset($user->profile_photo ?? 'uploads/user.png'),
            'token'         => $token,
        ];

        //  Success response
        return $this->success($userData, 'Successfully Logged In', 200);
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


    // user signup
    public function signup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255|min:3',
            'last_name' => 'nullable',
            'address' => 'nullable',
            'phone' => 'nullable',
            'zipcode' => 'nullable',
            'email' => [
                'required',
                'unique:users,email',
                'email:rfc,dns,filter',
                'max:255',
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation Error', 422);
        }


        $email = $request->email;
        $userData = [
            'first_name'     => $request->first_name,
            'last_name'     => $request->last_name,
            'address' => $request->address,
            'email'    => $request->email,
            'phone' => $request->phone,
            'zipcode' => $request->zipcode,
            'password' => Hash::make($request->password),
        ];

        // store pending data in cache
        cache()->put("pending_user_{$email}", $userData, now()->addMinutes(5));
        $otp = rand(100000, 999999);
        cache()->put(
            "otp_data_{$email}",
            [
                'otp' => $otp,
                'used' => false
            ],
            now()->addMinutes(5)
        );

        Mail::to($email)->send(new OtpSend($otp));
        return $this->success(null, 'OTP sent to your email, Please verify it.', 200);
    }

    public function verifyEmailOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
            'email' => 'required|email',
        ]);

        $email = $request->email;
        $enteredOtp = $request->otp;

        // Get OTP data from cache
        $otpData = cache()->get("otp_data_{$email}");

        if (!$otpData || $otpData == null) {
            return $this->error([], 'OTP expired.', 400);
        }

        if ($otpData['used']) {
            return $this->error([], 'OTP already used.', 400);
        }

        if ($enteredOtp != $otpData['otp']) {
            return $this->error([], 'OTP did not match.', 400);
        }

        // Get pending user
        $pendingUser = cache()->get("pending_user_{$email}");
        if (!$pendingUser) {
            return $this->error([], 'User data not found.', 400);
        }

        // Create user
        $user = User::create([
            'name' => $pendingUser['first_name'] . ' ' . $pendingUser['last_name'],
            'first_name'    => $pendingUser['first_name'],
            'last_name'     => $pendingUser['last_name'],
            'address'       => $pendingUser['address'],
            'email'         => $pendingUser['email'],
            'phone'         => $pendingUser['phone'],
            'zipcode'       => $pendingUser['zipcode'],
            'password'      => $pendingUser['password'],
            'last_login_at' => now(),
        ]);

        cache()->put("otp_data_{$email}", [
            'otp' => $otpData['otp'],
            'used' => true
        ], now()->addMinutes(1 / 2));

        cache()->forget("pending_user_{$email}");
        cache()->forget("otp_data_{$email}");

        Auth::guard('api')->login($user);
        return $this->success($user, 'OTP verified and user registered.', 200);
    }


    // forgot password
    public function sendOtp(Request $request)
    {
        // Validate incoming email
        $request->validate([
            'email' => [
                'required',
                'max:255',
            ]
        ]);

        // Check if user exists
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return $this->success((object)[], 'User with this email does not exist.', 200);
        }

        // Generate OTP and expiry
        $otp = rand(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes(5);

        // Save OTP and expiry to user
        $user->update([
            'otp' => $otp,
            'otp_expired_at' => $expiresAt,
        ]);

        // Send OTP via email
        Mail::to($user->email)->send(new OtpSend($otp));

        return $this->success([
            'email' => $user->email,
            'expires_at' => $expiresAt,
            // 'otp' => $otp     // Only expose OTP in development
        ], 'OTP sent successfully.', 200);
    }

    public function verifyOtp(Request $request)
    {

        $request->validate([
            'otp' => 'required|numeric|digits:6',
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->where('otp', $request->otp)->first();

        if (!$user) {
            return $this->error([], 'User not available for this email', 400);
        } else if ($user->otp_expired_at < Carbon::now()) {

            $user->otp = null;
            $user->otp_expired_at = null;
            $user->save();
            return $this->success((object)[], 'OTP expired', 410);
        }

        $user->otp_verified_at                 = Carbon::now();
        $user->password_reset_token            = Str::random(64);
        $user->password_reset_token_expires_at = Carbon::now()->addMinutes(5);
        $user->save();

        return $this->success([
            'email' => $user->email,
            'reset_token' => $user->password_reset_token,
        ], 'OTP verified successfully', 200);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => [
                'required',
                'email',
                'max:255',

            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
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
            return $this->error((object)[], 'Token expired.', 200);
        }
        $user->password = bcrypt($request->password);


        // Attempt login with email and password
        $credentials = $request->only('email', 'password');
        $token = Auth::guard('api')->attempt($credentials);

        // Format user data
        $userData = [
            'id'            => $user->id,
            'token'         => $token,
            'name'          => $user->name == null ? '' : $user->name,
            'email'         => $user->email,
            'username'      => $user->username,
            'profile_photo' => asset($user->profile_photo == null ? 'user.png' : $user->profile_photo),
        ];

        // Invalidate token after use
        $user->password_reset_token = null;
        $user->password_reset_token_expires_at = null;
        $user->save();

        return $this->success($userData, 'Login Successfull', 200);
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
