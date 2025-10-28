<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        Company::insert([
            [
                'commercial_name' => 'TechNova Solutions',
                'company_email' => 'info@technova.com',
                'fiscal_name' => 'TechNova Solutions Ltd.',
                'nif' => '23456',
                'phone_number' => '01770001111',
                'incubation_type' => 'on-site',
                'occupied_office' => 'Office A-201',
                'occupied_area' => '350 sq ft',
                'bussiness_area' => 'Software Development',
                'company_manager' => 'Rashed Karim',
                'description' => 'A software company specializing in Laravel and AI-based products.',
                'logo' => 'uploads/company_logos/technova.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'commercial_name' => 'GreenLeaf Industries',
                'company_email' => 'contact@greenleaf.com',
                'fiscal_name' => 'GreenLeaf Agro Industries',
                'nif' => '654321',
                'phone_number' => '01888002222',
                'incubation_type' => 'on-site',
                'occupied_office' => 'Office B-102',
                'occupied_area' => '420 sq ft',
                'bussiness_area' => 'Agriculture & Biotech',
                'company_manager' => 'Mariam Sultana',
                'description' => 'Leading provider of organic and eco-friendly agricultural products.',
                'logo' => 'uploads/company_logos/greenleaf.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'commercial_name' => 'Delta IT Hub',
                'company_email' => 'support@deltahub.com',
                'fiscal_name' => 'Delta IT Hub Pvt. Ltd.',
                'nif' => '789456',
                'phone_number' => '01999003333',
                'incubation_type' => 'virtual',
                'occupied_office' => 'Office C-301',
                'occupied_area' => '600 sq ft',
                'bussiness_area' => 'IT & Digital Services',
                'company_manager' => 'Sajid Ahmed',
                'description' => 'Incubating tech startups and providing IT consultancy services.',
                'logo' => 'uploads/company_logos/delta.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
