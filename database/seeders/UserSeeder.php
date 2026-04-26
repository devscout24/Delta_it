<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        foreach (['admin', 'user', 'company_user'] as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        }

        // Admin web account
        $adminData = [
            'name' => 'Admin User',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('12345678'),
            'user_type' => 'admin',
            'status' => 'active',
            'terms_and_conditions' => true,
        ];

        $adminUser = User::updateOrCreate(
            ['username' => $adminData['username']],
            $adminData
        );

        $adminUser->syncRoles(['admin']);

        // Mobile app account
        $mobileData = [
            'name' => 'Mobile User',
            'username' => 'mobileuser',
            'email' => 'mobile@example.com',
            'password' => Hash::make('12345678'),
            'user_type' => 'user',
            'status' => 'active',
            'terms_and_conditions' => true,
        ];

        $mobileUser = User::updateOrCreate(
            ['username' => $mobileData['username']],
            $mobileData
        );

        $mobileUser->syncRoles(['user']);
    }
}
