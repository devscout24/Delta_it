<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\Tag;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // CompanySeeder::class,
            UserSeeder::class,
            // DaySeeder::class,
            // RoomSeeder::class,
            // MeetingSeeder::class,
        ]);

        DB::table('companies')->insert([
            [
                'name' => 'TechNova Solutions',
                'email' => 'info@technova.com',
                'fiscal_name' => 'TechNova S.A.',
                'nif' => 'TNX-12345',
                'phone' => '+1234567890',
                'incubation_type' => 'virtual',
                'business_area' => 'Software Development',
                'manager' => 'John Doe',
                'description' => 'A company focused on building innovative SaaS platforms.',
                'logo' => 'logos/technova.png',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'GreenField Labs',
                'email' => 'contact@greenfieldlabs.io',
                'fiscal_name' => 'GreenField Innovations Ltd.',
                'nif' => 'GFL-67890',
                'phone' => '+1987654321',
                'incubation_type' => 'on-site',
                'business_area' => 'Agritech',
                'manager' => 'Sarah Johnson',
                'description' => 'Developing sustainable agricultural technology solutions.',
                'logo' => 'logos/greenfield.png',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'BlueSky Ventures',
                'email' => 'hello@bluesky.com',
                'fiscal_name' => 'BlueSky Global Inc.',
                'nif' => 'BSV-33445',
                'phone' => '+44123456789',
                'incubation_type' => 'cowork',
                'business_area' => 'Venture Capital',
                'manager' => 'Michael Smith',
                'description' => 'Investing in high-potential startups across multiple sectors.',
                'logo' => 'logos/bluesky.png',
                'status' => 'archived',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);


        DB::table('users')->insert([
            [
                'company_id' => null,
                'username' => 'admin',
                'name' => 'System',
                'last_name' => 'Admin',
                'email' => 'admin@example.com',
                'phone' => '+10000000000',
                'address' => '123 Admin Street',
                'zipcode' => '00000',
                'password' => Hash::make('12345678'),
                'profile_photo' => null,
                'user_type' => 'admin',
                'email_verified_at' => now(),
                'status' => 'active',
                'terms_and_conditions' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => 1,
                'username' => 'companyuser',
                'name' => 'Jane',
                'last_name' => 'Doe',
                'email' => 'jane.doe@technova.com',
                'phone' => '+15551234567',
                'address' => '456 Company Road',
                'zipcode' => '12345',
                'password' => Hash::make('12345678'),
                'profile_photo' => null,
                'user_type' => 'company_user',
                'email_verified_at' => now(),
                'status' => 'active',
                'terms_and_conditions' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('contracts')->insert([
            'company_id' => 1,
            'name' => 'TechNova Employment Agreement',
            'type' => 'full-time',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'renewal_date' => '2026-01-01',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('access_cards')->insert([
            'company_id' => 1,
            'active_card' => 25,
            'lost_damage_card' => 2,
            'active_parking_card' => 10,
            'max_parking_card' => 15,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $documents = [
            [
                'company_id' => 1,
                'document_name' => 'Company Policy Handbook',
                'document_type' => 'pdf',
                'document_path' => 'uploads/documents/company_policy.pdf',
            ],
            [
                'company_id' => 1,
                'document_name' => 'Employee Contract Template',
                'document_type' => 'pdf',
                'document_path' => 'uploads/documents/employee_contract.pdf',
            ],
            [
                'company_id' => 1,
                'document_name' => 'Office Layout Plan',
                'document_type' => 'pdf',
                'document_path' => 'uploads/documents/office_layout.pdf',
            ],
        ];

        foreach ($documents as $document) {
            Document::create($document);
        }

        $tags = [
            ['document_id' => 1, 'tag' => 'company-policy'],
            ['document_id' => 1, 'tag' => 'hr'],
            ['document_id' => 1, 'tag' => 'internal'],

            ['document_id' => 2, 'tag' => 'contract'],
            ['document_id' => 2, 'tag' => 'employee'],
            ['document_id' => 2, 'tag' => 'template'],

            ['document_id' => 3, 'tag' => 'office'],
            ['document_id' => 3, 'tag' => 'layout'],
            ['document_id' => 3, 'tag' => 'floor-plan'],
        ];

        foreach ($tags as $tag) {
            Tag::create($tag);
        }
    }
}
