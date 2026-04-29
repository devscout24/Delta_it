<?php

namespace Database\Seeders;

use App\Models\CompanyPayment;
use App\Models\Document;
use App\Models\Meeting;
use App\Models\MeetingBooking;
use App\Models\MeetingBookingAvailabilities;
use App\Models\MeetingBookingAvailabilitySlot;
use App\Models\MeetingBookingSchedule;
use App\Models\Room;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

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
            SpaceSeeder::class,
        ]);
    }
}
