<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Validator;

class AccountController extends Controller
{
    use ApiResponse;

    // List all accounts
    public function index()
    {
        $accounts = User::whereNull('name')->get();
        return $this->success($accounts, 'Account list retrieved successfully', 200);
    }

    // Create account
    public function store(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name'  => 'required|string|max:255',
                'job_position' => 'nullable|string|max:255',
                'email'      => 'required|email|unique:accounts,email',
                'password'   => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }


            $account = User::create($request->all());
            return $this->success($account, 'Account created successfully', 201);
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }

    // Update account
    public function update(Request $request)
    {
        $account = User::find($request->id);
        if (!$account) {
            return $this->error(null, 'Account not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name'  => 'sometimes|required|string|max:255',
            'job_position' => 'nullable|string|max:255',
            'email'      => 'sometimes|required|email|unique:accounts,email,' . $request->id,
            'password'   => 'sometimes|required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->error(null, $validator->errors(), 422);
        }

        $account->update($request->all());
        return $this->success($account, 'Account updated successfully', 200);
    }

    // Delete account
    public function destroy($id)
    {

        $account = User::find($id);
        if (!$account) {
            return $this->error(null, 'Account not found', 404);
        }

        $account->delete();
        return $this->success(null, 'Account deleted successfully', 200);
    }


    public function PasswordReset(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'new_password' => 'required|string|confirmed|min:6',
        ]);

        if ($validator->fails()) {
            return $this->error(null, $validator->errors(), 422);
        }

        $user  = new User();
        $user->where('id', auth()->guard('api')->user()->id)
            ->update(['password' => bcrypt($request->new_password)]);



        return $this->success(null, 'Password reset successfully', 200);
    }
}
