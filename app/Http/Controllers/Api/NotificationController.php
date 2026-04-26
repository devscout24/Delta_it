<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\NewSystemNotification;
use App\Traits\ApiResponse;
use Carbon\Carbon;
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
            'time' => 'required',
        ]);

        $user = User::find($request->user_id);

        $user->notify(new NewSystemNotification(
            $request->title,
            $request->description,
            $request->time
        ));

        return $this->success([], 'Notification sent', 201);
    }

    public function notification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tab' => 'nullable|in:all,unread',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        $user = Auth::guard('api')->user();
        if (! $user) {
            return $this->error([], 'User not found', 404);
        }

        $tab = $request->get('tab', 'all');

        $query = $user->notifications()->latest();
        if ($tab === 'unread') {
            $query->whereNull('read_at');
        }

        $notifications = $query->get()->map(function ($notification) {
            return $this->formatNotification($notification);
        })->values();

        $newNotifications = $notifications->filter(function ($item) {
            return $item['is_new'];
        })->values();

        $olderNotifications = $notifications->filter(function ($item) {
            return ! $item['is_new'];
        })->values();

        return $this->success([
            'tabs' => [
                'all' => $user->notifications()->count(),
                'unread' => $user->unreadNotifications()->count(),
            ],
            'active_tab' => $tab,
            'can_mark_all_read' => $user->unreadNotifications()->count() > 0,
            'new' => $newNotifications,
            'older' => $olderNotifications,
            'notifications' => $notifications,
        ], 'Notifications fetched successfully', 200);
    }

    public function markAllRead()
    {
        $user = Auth::guard('api')->user();
        if (! $user) {
            return $this->error([], 'User not found', 404);
        }

        $user->unreadNotifications->markAsRead();

        return $this->success([], 'All notifications marked as read', 200);
    }

    public function markAllUnread()
    {
        $user = Auth::guard('api')->user();
        if (! $user) {
            return $this->error([], 'User not found', 404);
        }

        $user->notifications()->whereNotNull('read_at')->update(['read_at' => null]);

        return $this->success([], 'All notifications marked as unread', 200);
    }

    public function deleteAll()
    {
        $user = Auth::guard('api')->user();
        if (! $user) {
            return $this->error([], 'User not found', 404);
        }

        $user->notifications()->delete();

        return $this->success([], 'All notifications deleted', 200);
    }

    public function deleteNotification(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id' => 'required|string',
        ]);

        if ($validation->fails()) {
            return $this->error($validation->errors(), 'Error in Validation', 422);
        }

        $user = Auth::guard('api')->user();
        if (! $user) {
            return $this->error([], 'User not found', 404);
        }

        $deleted = $user->notifications()->where('id', $request->id)->delete();
        if (! $deleted) {
            return $this->error([], 'Notification not found', 404);
        }

        return $this->success([], 'Notification deleted', 200);
    }

    public function markNotificationRead(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id' => 'required|string',
        ]);

        if ($validation->fails()) {
            return $this->error($validation->errors(), 'Error in Validation', 422);
        }

        $user = Auth::guard('api')->user();
        if (! $user) {
            return $this->error([], 'User not found', 404);
        }

        $notification = $user->notifications()->where('id', $request->id)->first();
        if (! $notification) {
            return $this->error([], 'Notification not found', 404);
        }

        $notification->markAsRead();

        return $this->success([], 'Notification marked as read', 200);
    }

    public function markNotificationUnread(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id' => 'required|string',
        ]);

        if ($validation->fails()) {
            return $this->error($validation->errors(), 'Error in Validation', 422);
        }

        $user = Auth::guard('api')->user();
        if (! $user) {
            return $this->error([], 'User not found', 404);
        }

        $updated = $user->notifications()->where('id', $request->id)->update(['read_at' => null]);
        if (! $updated) {
            return $this->error([], 'Notification not found', 404);
        }

        return $this->success([], 'Notification marked as unread', 200);
    }

    public function getNotifications(Request $request)
    {
        return $this->notification($request);
    }

    public function unread(Request $request)
    {
        $request->merge(['tab' => 'unread']);
        return $this->notification($request);
    }

    public function markRead(Request $request)
    {
        if ($request->filled('id')) {
            return $this->markNotificationRead($request);
        }

        return $this->markAllRead();
    }

    public function delete(Request $request)
    {
        if ($request->filled('id')) {
            return $this->deleteNotification($request);
        }

        return $this->deleteAll();
    }

    private function formatNotification($notification): array
    {
        $createdAt = $notification->created_at instanceof Carbon
            ? $notification->created_at
            : Carbon::parse($notification->created_at);

        return [
            'id' => $notification->id,
            'title' => $notification->data['title'] ?? 'Notification',
            'description' => $notification->data['description'] ?? null,
            'time' => $notification->data['time'] ?? null,
            'time_ago' => $createdAt->diffForHumans(now(), [
                'parts' => 1,
                'short' => true,
                'syntax' => Carbon::DIFF_ABSOLUTE,
            ]),
            'is_read' => $notification->read_at !== null,
            'is_new' => $createdAt->greaterThanOrEqualTo(now()->subHours(24)),
            'read_at' => $notification->read_at,
            'created_at' => $createdAt->toDateTimeString(),
        ];
    }
}
