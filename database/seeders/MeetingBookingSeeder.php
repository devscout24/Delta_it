<?php

namespace Database\Seeders;

use App\Models\MeetingBooking;
use App\Models\MeetingEvent;
use App\Models\MeetingEventSlot;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MeetingBookingSeeder extends Seeder
{
    // Matches the demo user created by UserSeeder
    private const DEMO_EMAIL = 'user@user.com';
    private const DEMO_NAME  = 'Demo User';

    public function run(): void
    {
        // 2 approved virtual bookings → shows in /my-meetings
        $this->seedVirtualBookings();

        // 3 physical bookings (mixed status) → shows in /my-bookings
        $this->seedPhysicalBookings();
    }

    private function seedVirtualBookings(): void
    {
        $events = MeetingEvent::where('type', 'virtual')->take(2)->get();

        foreach ($events as $event) {
            $slot = $this->getFreeSlot($event->id);
            if (!$slot) continue;

            $slot->update(['is_booked' => true]);

            MeetingBooking::create([
                'event_id'   => $event->id,
                'date'       => $slot->date,
                'start_time' => $slot->start_time,
                'end_time'   => $slot->end_time,
                'name'       => self::DEMO_NAME,
                'email'      => self::DEMO_EMAIL,
                'status'     => 'approved',
            ]);
        }
    }

    private function seedPhysicalBookings(): void
    {
        $events   = MeetingEvent::where('type', 'physical')->take(3)->get();
        $statuses = ['approved', 'pending', 'pending'];

        foreach ($events as $i => $event) {
            $slot = $this->getFreeSlot($event->id);
            if (!$slot) continue;

            $slot->update(['is_booked' => true]);

            MeetingBooking::create([
                'event_id'   => $event->id,
                'date'       => $slot->date,
                'start_time' => $slot->start_time,
                'end_time'   => $slot->end_time,
                'name'       => self::DEMO_NAME,
                'email'      => self::DEMO_EMAIL,
                'status'     => $statuses[$i],
            ]);
        }
    }

    private function getFreeSlot(int $eventId): ?MeetingEventSlot
    {
        return MeetingEventSlot::where('event_id', $eventId)
            ->where('date', '>=', Carbon::today()->toDateString())
            ->where('is_booked', false)
            ->orderBy('date')
            ->orderBy('start_time')
            ->first();
    }
}
