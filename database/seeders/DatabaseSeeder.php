<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CompanySeeder::class,
            UserSeeder::class,
            FloorSeeder::class,
            RoomSeeder::class,
            RoomAllocationSeeder::class,
            ContractSeeder::class,
            ContractFileSeeder::class,
            InvoiceSeeder::class,
            InvoiceItemSeeder::class,
            PaymentSeeder::class,
            TicketSeeder::class,
            TicketMessageSeeder::class,
            TicketAttachmentSeeder::class,
            DocumentSeeder::class,
            NotificationSeeder::class,
            MeetingEventSeeder::class,
            MeetingBookingSeeder::class,
            SpaceSeeder::class,
        ]);
    }
}
