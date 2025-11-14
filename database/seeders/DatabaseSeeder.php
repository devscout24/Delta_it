<?php

namespace Database\Seeders;

use App\Models\CompanyPayment;
use App\Models\Document;
use App\Models\Meeting;
use App\Models\Room;
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

        $rooms = [
            [
                'floor' => 1,
                'room_name' => 'Conference Room A',
                'area' => 35.50,
                'polygon_points' => json_encode([[0, 0], [5, 0], [5, 7], [0, 7]]),
                'status' => 'available',
            ],
            [
                'floor' => 1,
                'room_name' => 'Meeting Room B',
                'area' => 20.75,
                'polygon_points' => json_encode([[0, 0], [4, 0], [4, 5], [0, 5]]),
                'status' => 'occupied',
            ],
            [
                'floor' => 2,
                'room_name' => 'Office 201',
                'area' => 15.00,
                'polygon_points' => json_encode([[0, 0], [3, 0], [3, 5], [0, 5]]),
                'status' => 'maintenance',
            ],
            [
                'floor' => 2,
                'room_name' => 'Office 202',
                'area' => 18.25,
                'polygon_points' => json_encode([[0, 0], [4, 0], [4, 4.5], [0, 4.5]]),
                'status' => 'available',
            ],
        ];

        foreach ($rooms as $room) {
            Room::create($room);
        }

        $meetings = [
            [
                'room_id' => 1,
                'company_id' => 1,
                'created_by' => 1,
                'meeting_name' => 'Project Kickoff Meeting',
                'date' => now()->toDateString(),
                'start_time' => '10:00:00',
                'end_time' => '11:30:00',
                'meeting_type' => 'office',
                'online_link' => null,
                'add_emails' => json_encode(['team1@company.com', 'team2@company.com']),
            ],
            [
                'room_id' => 2,
                'company_id' => 1,
                'created_by' => 1,
                'meeting_name' => 'Quarterly Strategy Call',
                'date' => now()->addDays(2)->toDateString(),
                'start_time' => '14:00:00',
                'end_time' => '15:30:00',
                'meeting_type' => 'virtual',
                'online_link' => 'https://meet.google.com/abc-defg-hij',
                'add_emails' => json_encode(['ceo@company.com', 'manager@company.com']),
            ],
            [
                'room_id' => 3,
                'company_id' => 1,
                'created_by' => 1,
                'meeting_name' => 'Design Review Session',
                'date' => now()->addDays(5)->toDateString(),
                'start_time' => '09:30:00',
                'end_time' => '11:00:00',
                'meeting_type' => 'office',
                'online_link' => null,
                'add_emails' => json_encode(['design@company.com', 'qa@company.com']),
            ],
        ];

        foreach ($meetings as $meeting) {
            Meeting::create($meeting);
        }

        $companyId = 1; // test company
        $year = 2025;

        for ($month = 1; $month <= 12; $month++) {
            CompanyPayment::create([
                'company_id' => $companyId,
                'year'       => $year,
                'month'      => $month,
            ]);
        }
    }
}
