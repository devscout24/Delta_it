<?php

namespace Database\Seeders;

use App\Models\User;
use App\Notifications\NewSystemNotification;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationSeeder extends Seeder
{
    /**
     * Seed demo notifications for mobile notification screens.
     */
    public function run(): void
    {
        $user = User::query()
            ->where('username', 'mobileuser')
            ->orWhere('email', 'mobile@example.com')
            ->first();

        if (! $user) {
            return;
        }

        DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->delete();

        $now = Carbon::now();
        $items = [
            [
                'title' => 'Ticket status updated',
                'description' => 'Your support ticket has moved to in progress.',
                'created_at' => $now->copy()->subMinute(),
                'read_at' => null,
            ],
            [
                'title' => 'Company activity updated',
                'description' => 'A new activity update is available for your company.',
                'created_at' => $now->copy()->subMinutes(5),
                'read_at' => null,
            ],
            [
                'title' => 'Contract reminder',
                'description' => 'Please review the latest contract renewal details.',
                'created_at' => $now->copy()->subMinutes(24),
                'read_at' => null,
            ],
            [
                'title' => 'Meeting schedule changed',
                'description' => 'Your next meeting has a new start time.',
                'created_at' => $now->copy()->subHours(2),
                'read_at' => $now->copy()->subHours(2),
            ],
            [
                'title' => 'Document uploaded',
                'description' => 'A new document is available in your workspace.',
                'created_at' => $now->copy()->subHours(5),
                'read_at' => $now->copy()->subHours(5),
            ],
            [
                'title' => 'Access card approved',
                'description' => 'Your access card request has been approved.',
                'created_at' => $now->copy()->subHours(17),
                'read_at' => $now->copy()->subHours(17),
            ],
        ];

        DB::table('notifications')->insert(array_map(function (array $item) use ($user) {
            return [
                'id' => (string) Str::uuid(),
                'type' => NewSystemNotification::class,
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'title' => $item['title'],
                    'description' => $item['description'],
                    'time' => $item['created_at']->toDateTimeString(),
                ]),
                'read_at' => $item['read_at'],
                'created_at' => $item['created_at'],
                'updated_at' => $item['created_at'],
            ];
        }, $items));
    }
}
