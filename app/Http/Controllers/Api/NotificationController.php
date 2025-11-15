<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\NewSystemNotification;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    use ApiResponse;
    public function create(Request $request)
    {
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
        return $this->success(Auth::guard('api')->user()->unreadNotifications, "Unread notifications", 200);
    }

    public function markRead(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return $this->error([], "User not found", 404);
        }
        $notification = Auth::guard('api')->user()
            ->notifications()
            ->where('id', $request->id)
            ->first();

        if (!$notification) {
            return $this->error([], "Notification not found", 404);
        }

        $notification->markAsRead();

        return $this->success([], "Marked as read", 200);
    }
    public function delete(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return $this->error([], "User not found", 404);
        }

        if (!$request->id) {
            return $this->error([], "Notification id not found", 404);
        }

        Auth::guard('api')->user()
            ->notifications()
            ->where('id', $request->id)
            ->delete();

        return $this->success([], "Notification deleted", 200);
    }
}
