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
    public function run()
    {
        $users = User::all();

        foreach ($users as $user) {

            for ($i = 1; $i <= 5; $i++) {

                DB::table('notifications')->insert([
                    'id' => Str::uuid(),
                    'type' => 'system',
                    'notifiable_type' => User::class,
                    'notifiable_id' => $user->id,
                    'data' => json_encode([
                        'title' => 'Notification Title ' . $i,
                        'message' => 'This is a sample notification message',
                    ]),
                    'read_at' => rand(0, 1) ? now() : null,
                    'created_at' => now()->subDays($i),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
