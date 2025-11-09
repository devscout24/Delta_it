<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AccountController extends Controller
{
    use ApiResponse;
    public function store(Request $request)
    {

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
        $account = Account::create($request->all());
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'address'    => $request->address,
            'email'      => $request->email,
            'phone'      => $request->phone,
            'zipcode'    => $request->zipcode,
            'password'   => Hash::make($request->password),
        ]);
        return $this->success($account, 'Account created successfully', 201);
    }

    // Update account
    public function update(Request $request)
    {
        $account = Account::find($request->id);
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
        $account = Account::find($id);
        if (!$account) {
            return $this->error(null, 'Account not found', 404);
        }

        $account->delete();
        return $this->success(null, 'Account deleted successfully', 200);
    }
}
