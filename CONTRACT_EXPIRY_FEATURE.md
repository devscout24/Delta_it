# Contract Expiry Reminder System

This document explains the contract expiry reminder feature that automatically sends email notifications to companies before their contracts expire.

## Overview

The system sends automated email reminders to companies at **30, 15, 7, and 4 days** before their contract end dates.

## Components Created

### 1. **Mail Class** - `ContractExpiryReminderMail`
- **Location:** `app/Mail/ContractExpiryReminderMail.php`
- **Purpose:** Formats and sends the expiry reminder emails
- **Template:** Uses `resources/views/emails/contract_expiry_reminder.blade.php`
- **Features:**
  - Customizable subject with days remaining
  - Rich HTML email template
  - Includes contract details and company information

### 2. **Queued Job** - `SendContractExpiryReminder`
- **Location:** `app/Jobs/SendContractExpiryReminder.php`
- **Purpose:** Handles email delivery asynchronously via the queue
- **Benefits:**
  - Non-blocking email sending
  - Can retry on failure
  - Scales with multiple queue workers

### 3. **Artisan Command** - `CheckContractExpiry`
- **Location:** `app/Console/Commands/CheckContractExpiry.php`
- **Command:** `php artisan contracts:check-expiry`
- **Purpose:** Scans all contracts and dispatches reminder jobs
- **Features:**
  - Checks for contracts expiring in 4, 7, 15, and 30 days
  - Prevents duplicate emails via logging
  - Only sends for active contracts (skips terminated/expired)

### 4. **Model** - `ContractNotificationLog`
- **Location:** `app/Models/ContractNotificationLog.php`
- **Purpose:** Tracks which notifications have been sent
- **Benefits:**
  - Prevents duplicate email reminders
  - Maintains audit trail of notifications
  - Unique constraint on `contract_id`, `days_remaining`, and `sent_at`

### 5. **Database Migration**
- **Location:** `database/migrations/2026_02_05_000000_create_contract_notification_logs_table.php`
- **Table:** `contract_notification_logs`
- **Columns:**
  - `id`: Primary key
  - `contract_id`: Foreign key to contracts table
  - `days_remaining`: Number of days before expiry when reminder was sent
  - `sent_at`: Timestamp of when notification was sent
  - `created_at`, `updated_at`: Timestamps

### 6. **Email Template**
- **Location:** `resources/views/emails/contract_expiry_reminder.blade.php`
- **Features:**
  - Professional HTML design
  - Displays contract details (name, type, dates, status)
  - Color-coded alerts (red for urgent reminders)
  - Recommended actions for the company
  - Responsive design

### 7. **Scheduler Configuration**
- **Location:** `routes/console.php`
- **Schedule:** Runs daily at 08:00 AM
- **Command:** `contracts:check-expiry`

## Installation & Setup

### Step 1: Run Migrations
```bash
php artisan migrate
```
This creates the `contract_notification_logs` table.

### Step 2: Test the Command (Optional)
```bash
php artisan contracts:check-expiry
```
Run manually to test the command.

### Step 3: Configure Queue Driver
Ensure your `.env` file has the queue driver configured:
```env
QUEUE_CONNECTION=database  # or redis, sync, etc.
```

### Step 4: Start Queue Worker
For production, run the queue worker:
```bash
php artisan queue:work
```

### Step 5: Set Up Scheduler
Ensure your server's cron job runs Laravel's scheduler:
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## How It Works

1. **Daily Check** (8:00 AM):
   - The scheduler runs `contracts:check-expiry`

2. **Contract Scanning**:
   - Finds all contracts expiring in 4, 7, 15, or 30 days
   - Skips terminated or expired contracts

3. **Duplicate Prevention**:
   - Checks if a reminder was already sent
   - Only sends if no recent notification exists

4. **Email Queue**:
   - Dispatches `SendContractExpiryReminder` job
   - Job sends the formatted email to company's email address
   - Logs the notification in database

5. **Email Delivery**:
   - Queue worker picks up the job
   - Sends email via configured mail driver
   - Email includes all contract details and actionable information

## Notification Schedule Example

For a contract expiring on **February 15, 2026**:

| When | Action |
|------|--------|
| Jan 16, 2026 | 30-day reminder email sent |
| Jan 31, 2026 | 15-day reminder email sent |
| Feb 8, 2026 | 7-day reminder email sent |
| Feb 11, 2026 | 4-day reminder email sent |
| Feb 15, 2026 | Contract expires |

## Configuration Options

### Modify Reminder Days
Edit `app/Console/Commands/CheckContractExpiry.php`:
```php
$remindDays = [4, 7, 15, 30]; // Change these numbers
```

### Change Schedule Time
Edit `routes/console.php`:
```php
Schedule::command('contracts:check-expiry')
    ->dailyAt('10:00')  // Change time here
    ->description('...');
```

### Change Email Template
Edit or create a new template in:
```
resources/views/emails/contract_expiry_reminder.blade.php
```

## Database Queries

### View all sent notifications:
```sql
SELECT * FROM contract_notification_logs 
ORDER BY created_at DESC;
```

### View reminders for a specific contract:
```sql
SELECT * FROM contract_notification_logs 
WHERE contract_id = 5 
ORDER BY created_at DESC;
```

### Check how many reminders were sent today:
```sql
SELECT COUNT(*) FROM contract_notification_logs 
WHERE DATE(created_at) = CURDATE();
```

## Troubleshooting

### Emails not sending?
1. Check queue is running: `php artisan queue:work`
2. Verify mail configuration in `.env`
3. Check logs in `storage/logs/laravel.log`

### Duplicate emails?
1. Check `contract_notification_logs` table
2. Ensure queue workers are not running multiple instances
3. Check for database constraints

### Scheduler not running?
1. Verify cron job is set up correctly
2. Check server timezone in `.env`
3. Run manually: `php artisan schedule:run`

## Related Models & Tables

- **Contracts Table:** Stores contract data with `end_date` field
- **Companies Table:** Stores company info with `email` field
- **Contract Files Table:** Stores associated files
- **Contract Associate Companies Table:** Links associate companies

## Email Configuration

For the emails to send, ensure `.env` has mail configuration:
```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io  # or your email provider
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Your Company Name"
```

## Testing

### Manual Test
```bash
# Run the command manually
php artisan contracts:check-expiry

# Check the logs
tail -f storage/logs/laravel.log

# View queued jobs
php artisan queue:failed
```

### Test with Specific Contract
Create a test contract with an end_date 4 days away and run the command.

## Future Enhancements

1. Add SMS notifications
2. Add webhook notifications
3. Add dashboard for notification history
4. Add customizable reminder days per company
5. Add email template customization per company
6. Add notification preferences UI
7. Add retry logic for failed emails
8. Add email open/click tracking

## Support

For issues or questions about this feature, please refer to:
- Mail configuration: `config/mail.php`
- Queue configuration: `config/queue.php`
- Scheduler documentation: [Laravel Scheduling](https://laravel.com/docs/scheduling)
