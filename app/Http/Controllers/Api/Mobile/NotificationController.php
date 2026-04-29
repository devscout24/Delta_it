<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    use ApiResponse;

    // ======================
    // LIST
    // ======================
    public function index()
    {
        $user = Auth::guard('api')->user();

        $notifications = $user->notifications()
            ->latest()
            ->get()
            ->map(function ($n) {
                $data = $n->data;

                return [
                    'id' => $n->id,
                    'title' => $data['title'] ?? '',
                    'message' => $data['message'] ?? '',
                    'is_read' => $n->read_at ? true : false,
                    'created_at' => $n->created_at->diffForHumans(),
                ];
            });

        return $this->success([
            'notifications' => $notifications,
            'unread_count' => $user->unreadNotifications()->count(),
        ], 'Notifications fetched');
    }

    // ======================
    // MARK ALL READ
    // ======================
    public function markAllRead()
    {
        $user = Auth::guard('api')->user();

        $user->unreadNotifications->markAsRead();

        return $this->success([], 'All notifications marked as read');
    }

    // ======================
    // MARK ALL UNREAD
    // ======================
    public function markAllUnread()
    {
        $user = Auth::guard('api')->user();

        DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->update(['read_at' => null]);

        return $this->success([], 'All notifications marked as unread');
    }

    // ======================
    // DELETE ALL
    // ======================
    public function deleteAll()
    {
        $user = Auth::guard('api')->user();

        $user->notifications()->delete();

        return $this->success([], 'All notifications deleted');
    }

    // ======================
    // DELETE SINGLE
    // ======================
    public function deleteNotification(Request $request)
    {
        $request->validate([
            'id' => 'required'
        ]);

        $user = Auth::guard('api')->user();

        $user->notifications()->where('id', $request->id)->delete();

        return $this->success([], 'Notification deleted');
    }

    // ======================
    // MARK READ
    // ======================
    public function markNotificationRead(Request $request)
    {
        $request->validate([
            'id' => 'required'
        ]);

        $user = Auth::guard('api')->user();

        $notification = $user->notifications()->find($request->id);

        if ($notification) {
            $notification->markAsRead();
        }

        return $this->success([], 'Notification marked as read');
    }

    // ======================
    // MARK UNREAD
    // ======================
    public function markNotificationUnread(Request $request)
    {
        $request->validate([
            'id' => 'required'
        ]);

        $user = Auth::guard('api')->user();

        DB::table('notifications')
            ->where('id', $request->id)
            ->update(['read_at' => null]);

        return $this->success([], 'Notification marked as unread');
    }
}
