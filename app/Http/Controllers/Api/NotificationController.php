<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\NewSystemNotification;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    use ApiResponse;
    public function create(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'time' => 'required'
        ]);

        $user = User::find($request->user_id);

        $user->notify(new NewSystemNotification(
            $request->title,
            $request->description,
            $request->time
        ));

        return $this->success([], "Notification sent", 201);
    }



    public function getNotifications(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return $this->error([], "User not found", 404);
        }

        return $this->success($user->notifications, "Notifications fetched", 200);
    }



    public function unread(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return $this->error([], "User not found", 404);
        }

        return $this->success($user->unreadNotifications, "Unread notifications", 200);
    }



    public function markRead(Request $request)
    {
        $validation = Validator::make($request->all(), [
             'id' => 'required|string'
        ]);

        if($validation->fails()) {
            return $this->error($validation->errors(), 'Error in Validation', 422);
        }


        $user = Auth::guard('api')->user();

        if (!$user) {
            return $this->error([], "User not found", 404);
        }

        $notification = $user->notifications()->where('id', $request->id)->first();

        if (!$notification) {
            return $this->error([], "Notification not found", 404);
        }

        $notification->markAsRead();

        return $this->success([], "Marked as read", 200);
    }



    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|string'
        ]);

        $user = Auth::guard('api')->user();

        if (!$user) {
            return $this->error([], "User not found", 404);
        }

        $deleted = $user->notifications()->where('id', $request->id)->delete();

        if (!$deleted) {
            return $this->error([], "Notification not found", 404);
        }

        return $this->success([], "Notification deleted", 200);
    }
}
