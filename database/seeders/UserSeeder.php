<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Company;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Admin User
        User::updateOrCreate([
            'email' => 'admin@demo.com',
        ], [
            'name' => 'Admin User',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'company_id' => null,
            'phone' => '0000000000',
            'job_title' => 'System Admin',
        ]);

        // Get companies
        $companies = Company::all();

        foreach ($companies as $company) {
            $baseEmail = strtolower(str_replace(' ', '', $company->name));

            // Company Manager
            User::updateOrCreate([
                'email' => $baseEmail . '@manager.com',
            ], [
                'name' => $company->name . ' Manager',
                'password' => Hash::make('password'),
                'role' => 'company_user',
                'status' => 'active',
                'company_id' => $company->id,
                'phone' => '123456789',
                'job_title' => 'Manager',
            ]);

            User::updateOrCreate([
                'email' => 'user@user.com',
            ], [
                'name' => $company->name . ' Manager',
                'password' => Hash::make('password'),
                'role' => 'company_user',
                'status' => 'active',
                'company_id' => $company->id,
                'phone' => '123456789',
                'job_title' => 'Manager',
            ]);



            // Another Employee
            User::updateOrCreate([
                'email' => $baseEmail . '@employee.com',
            ], [
                'name' => $company->name . ' Employee',
                'password' => Hash::make('password'),
                'role' => 'company_user',
                'status' => 'active',
                'company_id' => $company->id,
                'phone' => '987654321',
                'job_title' => 'Staff',
            ]);
        }
    }
}
