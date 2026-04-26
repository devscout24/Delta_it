<?php

namespace Database\Seeders;

use App\Models\User;
use App\Notifications\NewSystemNotification;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationSeeder extends Seeder
{
    /**
     * Seed demo notifications for mobile notification screens.
     */
    public function run(): void
    {
        $users = User::query()
            ->whereIn('user_type', ['user', 'company_user', 'reception'])
            ->take(3)
            ->get();

        if ($users->isEmpty()) {
            return;
        }

        foreach ($users as $user) {
            DB::table('notifications')
                ->where('notifiable_type', User::class)
                ->where('notifiable_id', $user->id)
                ->delete();

            $items = [
                [
                    'title' => 'Maintenance ticket updated',
                    'description' => 'Your maintenance request is now in progress.',
                    'created_at' => Carbon::now()->subMinutes(1),
                    'read_at' => null,
                ],
                [
                    'title' => 'Contract reminder',
                    'description' => 'Please review your contract renewal details.',
                    'created_at' => Carbon::now()->subMinutes(24),
                    'read_at' => null,
                ],
                [
                    'title' => 'Access card notice',
                    'description' => 'A new access card request has been approved.',
                    'created_at' => Carbon::now()->subHours(2),
                    'read_at' => Carbon::now()->subHours(2),
                ],
                [
                    'title' => 'Meeting schedule changed',
                    'description' => 'Your next meeting has a new start time.',
                    'created_at' => Carbon::now()->subHours(17),
                    'read_at' => Carbon::now()->subHours(15),
                ],
                [
                    'title' => 'Document uploaded',
                    'description' => 'A new shared document is available in your workspace.',
                    'created_at' => Carbon::now()->subDays(2),
                    'read_at' => Carbon::now()->subDays(2),
                ],
            ];

            foreach ($items as $item) {
                $notification = new NewSystemNotification(
                    $item['title'],
                    $item['description'],
                    $item['created_at']->toDateTimeString()
                );

                $user->notify($notification);

                $saved = $user->notifications()->latest()->first();
                if ($saved) {
                    $saved->forceFill([
                        'created_at' => $item['created_at'],
                        'updated_at' => $item['created_at'],
                        'read_at' => $item['read_at'],
                    ])->save();
                }
            }
        }
    }
}
