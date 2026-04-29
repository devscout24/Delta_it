# Event & Space Seeders Documentation

## Overview
Created comprehensive seeders for Meeting Events and Spaces that populate the database with realistic booking data. Users can now see and book events and spaces from the application.

---

## Meeting Events Seeder
**File:** `database/seeders/MeetingEventSeeder.php`

### Virtual Meeting Events (4 events)
1. **Weekly Tech Standup**
   - Duration: 30 minutes
   - Max Invitees: 20
   - Meeting Link: Zoom
   - Description: Weekly team sync-up meeting for tech discussions
   - Color: #4A90E2 (Blue)

2. **Product Roadmap Review**
   - Duration: 60 minutes
   - Max Invitees: 15
   - Meeting Link: Google Meet
   - Description: Quarterly product roadmap and feature planning
   - Color: #7ED321 (Green)

3. **Investor Webinar**
   - Duration: 90 minutes
   - Max Invitees: 100
   - Meeting Link: Custom webinar platform
   - Description: Monthly webinar for investors and stakeholders
   - Color: #F5A623 (Orange)

4. **Training Session - Laravel**
   - Duration: 120 minutes
   - Max Invitees: 50
   - Meeting Link: Training platform
   - Description: Comprehensive Laravel framework training session
   - Color: #FF6B6B (Red)

### Physical Meeting Events (4 events)
1. **Networking Breakfast**
   - Duration: 90 minutes
   - Max Invitees: 30
   - Location: Main Conference Room - Floor 2
   - Description: Monthly networking breakfast for all team members
   - Color: #BD10E0 (Purple)

2. **Team Building Workshop**
   - Duration: 180 minutes
   - Max Invitees: 25
   - Location: Workshop Area - Floor 1
   - Description: Interactive team building and collaboration workshop
   - Color: #50E3C2 (Teal)

3. **Client Presentation**
   - Duration: 60 minutes
   - Max Invitees: 20
   - Location: Executive Board Room - Floor 3
   - Description: Client project presentation and feedback session
   - Color: #B8E986 (Light Green)

4. **Partner Summit**
   - Duration: 240 minutes
   - Max Invitees: 80
   - Location: Grand Hall - Floor 4
   - Description: Annual partner summit with keynote speeches
   - Color: #FF4081 (Pink)

### Meeting Event Data Structure
Each event includes:
- **3 Schedules**: One for each of the next 3 months
- **Schedule Days**: 2-3 recurring days per week (Monday-Friday)
- **Time Slots**: Automatically generated based on event duration
  - Slot duration: Event duration + 15 min break
  - Operating hours: 09:00-17:00 (customizable)
  - Random booking status (some slots pre-booked)

### Seeded Statistics
- Total Meeting Events: 8
- Total Schedules: 24
- Total Event Slots: 278

---

## Spaces Seeder
**File:** `database/seeders/SpaceSeeder.php`

### Spaces (8 rooms)
1. **Conference Room A**
   - Capacity: 20 people
   - Color: #4A90E2 (Blue)
   - Description: Professional conference room with AV equipment, whiteboard, and comfortable seating
   - Hours: 07:00-20:00 (including Saturday 09:00-17:00)

2. **Meeting Room B**
   - Capacity: 8 people
   - Color: #7ED321 (Green)
   - Description: Intimate meeting space perfect for one-on-one or small team discussions
   - Hours: 08:00-18:00

3. **Collaboration Hub**
   - Capacity: 15 people
   - Color: #F5A623 (Orange)
   - Description: Open collaborative space with modern furniture and digital display screens
   - Hours: 08:00-18:00

4. **Training Center**
   - Capacity: 40 people
   - Color: #FF6B6B (Red)
   - Description: Large training facility with projectors, interactive boards, and classroom setup
   - Hours: 07:00-20:00 (including Saturday 09:00-17:00)

5. **Executive Board Room**
   - Capacity: 25 people
   - Color: #BD10E0 (Purple)
   - Description: High-end boardroom with video conferencing, premium equipment, and elegant furnishings
   - Hours: 07:00-20:00 (including Saturday 09:00-17:00)

6. **Breakout Area**
   - Capacity: 12 people
   - Color: #50E3C2 (Teal)
   - Description: Casual space for informal meetings, brainstorming, and team gatherings
   - Hours: 08:00-18:00

7. **Innovation Lab**
   - Capacity: 18 people
   - Color: #B8E986 (Light Green)
   - Description: Specialized workspace for creative projects with modern tech and flexible layout
   - Hours: 08:00-18:00

8. **Quiet Zone**
   - Capacity: 5 people
   - Color: #FF4081 (Pink)
   - Description: Soundproof individual focus rooms for concentrated work and private calls
   - Hours: 08:00-17:00 (Monday-Friday only)

### Space Data Structure
Each space includes:
- **3 Schedules**: One for each of the next 3 months
- **Schedule Days**: Recurring days of week with operating hours
  - Standard rooms: Monday-Friday 08:00-18:00
  - Extended hours: Conference/Training rooms add Saturday 09:00-17:00
  - Quiet Zone: Monday-Friday 08:00-17:00
- **Time Slots**: 30-minute booking slots
  - Created for every day in the schedule period
  - Random booking status (~33% pre-booked)

### Seeded Statistics
- Total Spaces: 8
- Total Space Schedules: 24
- Total Space Schedule Days: 129
- Total Space Slots: 12,064

---

## Seeding Features

### Intelligent Slot Generation
1. **Meeting Events**: Slots are created based on event duration with 15-minute breaks
2. **Spaces**: Fixed 30-minute slots across all operating hours

### Realistic Booking Data
- Some slots are randomly pre-booked (meeting events: 50%, spaces: 33%)
- Allows users to see available vs. booked slots
- Realistic availability patterns

### Multi-Month Coverage
- All seeders create data for 3 months (current + 2 future months)
- Recurring schedules for predictable bookings
- Real dates starting from current month

### Color-Coded
- Each event and space has a unique color for UI display
- Helps users quickly identify different meeting types and spaces

---

## How to Run

### Fresh Database with All Seeders
```bash
php artisan migrate:fresh --seed
```

### Run Only Event & Space Seeders
```bash
php artisan db:seed --class=MeetingEventSeeder
php artisan db:seed --class=SpaceSeeder
```

---

## Data Relationships

### Meeting Events Flow
```
MeetingEvent
├── MeetingEventSchedule (3 per event)
│   ├── MeetingEventScheduleDay (2-3 per schedule)
│   └── MeetingEventSlot (multiple per schedule)
└── MeetingBooking (when users book)
```

### Spaces Flow
```
Space
├── SpaceSchedule (3 per space)
│   ├── SpaceScheduleDay (5-6 per schedule)
│   └── SpaceSlot (multiple per schedule)
└── SpaceBooking (when users book)
```

---

## Usage in Application

### For Mobile/Web App Users
1. Users navigate to Events/Spaces section
2. See available meeting events and spaces with:
   - Title, description, capacity
   - Available time slots
   - Color-coded categories
3. Select a slot and confirm booking
4. Booking creates MeetingBooking or SpaceBooking record

### For Admins/Managers
- View booking statistics
- See utilization rates
- Modify schedules and availability
- Add/remove time slots as needed

---

## Future Enhancements

### Potential Additions
1. **Recurring Patterns**: Create annual recurring schedules
2. **Dynamic Pricing**: Different rates for different times
3. **Capacity Optimization**: Suggest best-fit spaces based on attendee count
4. **Waitlist**: Handle overbooking scenarios
5. **Notifications**: Alert users of upcoming events/bookings
6. **Analytics**: Track most popular times and spaces

