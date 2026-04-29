<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;

class CompanySeeder extends Seeder
{
    public function run()
    {
        Company::create([
            'name' => 'TechNova Solutions',
            'email' => 'info@technova.com',
            'phone' => '+1234567890',
            'nif' => 'TNX-12345',
            'incubation_type' => 'virtual',
            'business_area' => 'Software Development',
            'manager_name' => 'John Doe',
            'description' => 'SaaS product company',
            'status' => 'active',
        ]);

        Company::create([
            'name' => 'GreenField Labs',
            'email' => 'contact@greenfieldlabs.io',
            'phone' => '+1987654321',
            'nif' => 'GFL-67890',
            'incubation_type' => 'on-site',
            'business_area' => 'Agritech',
            'manager_name' => 'Sarah Johnson',
            'description' => 'Agri innovation startup',
            'status' => 'active',
        ]);

        Company::create([
            'name' => 'BlueSky Ventures',
            'email' => 'hello@bluesky.com',
            'phone' => '+44123456789',
            'nif' => 'BSV-33445',
            'incubation_type' => 'cowork',
            'business_area' => 'Investment',
            'manager_name' => 'Michael Smith',
            'description' => 'VC firm',
            'status' => 'archived',
        ]);
    }
}
