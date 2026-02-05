# Quick Setup Guide: Contract Expiry Reminder Feature

## 🚀 Quick Start (5 minutes)

### 1. Run the Migration
```bash
php artisan migrate
```
This creates the `contract_notification_logs` table to track sent notifications.

### 2. Configure Mail (.env)
Ensure your `.env` file has proper mail configuration:
```env
MAIL_DRIVER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Your Company"
```

### 3. Configure Queue (.env)
```env
QUEUE_CONNECTION=database
# or use redis, sync, etc.
```

### 4. Start Queue Worker
```bash
php artisan queue:work
```
Run this in a separate terminal to process queued emails.

### 5. Test the Command
```bash
php artisan contracts:check-expiry
```

### 6. Set Up Cron (Production Only)
Add to your server crontab:
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 📋 Files Created

| File | Purpose |
|------|---------|
| `app/Mail/ContractExpiryReminderMail.php` | Email formatter |
| `app/Jobs/SendContractExpiryReminder.php` | Queued job for async sending |
| `app/Console/Commands/CheckContractExpiry.php` | Checks contracts daily |
| `app/Models/ContractNotificationLog.php` | Tracks sent notifications |
| `database/migrations/2026_02_05_000000_create_contract_notification_logs_table.php` | Database table |
| `resources/views/emails/contract_expiry_reminder.blade.php` | Email template |
| `routes/console.php` | Updated with scheduler |

---

## ⏰ Reminder Schedule

The system sends reminders **30, 15, 7, and 4 days** before contract expiry.

Example: Contract expires Feb 15
- Jan 16: 30-day reminder ✉️
- Jan 31: 15-day reminder ✉️
- Feb 8: 7-day reminder ✉️
- Feb 11: 4-day reminder ✉️

---

## 🔧 Customization

### Change Reminder Days
Edit `app/Console/Commands/CheckContractExpiry.php`:
```php
$remindDays = [4, 7, 15, 30]; // Your desired days
```

### Change Schedule Time
Edit `routes/console.php`:
```php
->dailyAt('08:00') // Change time here
```

### Customize Email Template
Edit `resources/views/emails/contract_expiry_reminder.blade.php`

---

## ✅ Verification Checklist

- [ ] Migration ran successfully: `php artisan migrate`
- [ ] Mail configuration is set in `.env`
- [ ] Queue worker is running
- [ ] Cron job is configured (if production)
- [ ] Test command ran: `php artisan contracts:check-expiry`
- [ ] Check logs: `tail -f storage/logs/laravel.log`
- [ ] Verify table: Check `contract_notification_logs` table exists

---

## 📞 Support

For detailed documentation, see: `CONTRACT_EXPIRY_FEATURE.md`
