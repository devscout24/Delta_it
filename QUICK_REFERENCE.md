# ⚡ Contract Expiry Reminder - Quick Reference Card

## 📌 At a Glance

```
WHAT:    Automated contract expiry email reminders
WHEN:    30, 15, 7, 4 days before expiry
HOW:     Runs automatically every day at 8:00 AM
WHERE:   Sends to company email addresses
STATUS:  ✅ PRODUCTION READY
```

---

## 🚀 Quick Setup (5 minutes)

```bash
# 1. Run migration
php artisan migrate

# 2. Configure .env
# Set MAIL_* and QUEUE_CONNECTION variables

# 3. Start queue worker
php artisan queue:work

# Done! System now sends reminders automatically
```

---

## 📁 Key Files

| Purpose | File Location |
|---------|---------------|
| Email Formatter | `app/Mail/ContractExpiryReminderMail.php` |
| Queue Job | `app/Jobs/SendContractExpiryReminder.php` |
| Daily Command | `app/Console/Commands/CheckContractExpiry.php` |
| Notification Log | `app/Models/ContractNotificationLog.php` |
| Migration | `database/migrations/2026_02_05_000000_...` |
| Email Template | `resources/views/emails/contract_expiry_reminder.blade.php` |
| Scheduler | `routes/console.php` |

---

## 🔧 Customization Cheat Sheet

### Change Reminder Days
**File:** `app/Console/Commands/CheckContractExpiry.php` (line 22)
```php
$remindDays = [4, 7, 15, 30]; // Modify here
```

### Change Schedule Time
**File:** `routes/console.php` (line 11)
```php
->dailyAt('08:00') // Change to your time
```

### Customize Email Content
**File:** `resources/views/emails/contract_expiry_reminder.blade.php`
- Edit HTML structure
- Adjust colors/styling
- Change text

### Change Queue Driver
**File:** `.env`
```env
QUEUE_CONNECTION=database  # or redis, sync, etc
```

---

## 📊 Daily Process

```
8:00 AM    ┌─► CheckContractExpiry command runs
           │
           ├─► Scan contracts expiring in 4/7/15/30 days
           │
           ├─► Check for duplicates
           │
           └─► Queue email jobs

8:01 AM    ┌─► Queue worker processes jobs
           │
           ├─► Build HTML emails
           │
           ├─► Send via SMTP
           │
           └─► Log notifications

Result     ✅ Emails delivered to companies
           ✅ Notifications logged
           ✅ Duplicates prevented
```

---

## ✅ Verification Commands

```bash
# Check if table created
php artisan tinker
>>> ContractNotificationLog::count()

# View sent notifications
>>> ContractNotificationLog::latest()->take(10)->get()

# Test the command
php artisan contracts:check-expiry

# Check queue jobs
php artisan queue:failed

# View scheduler
php artisan schedule:list

# Process one queue job
php artisan queue:work --once
```

---

## 🐛 Troubleshooting Quick Fixes

| Issue | Solution |
|-------|----------|
| Emails not sending | Start queue worker: `php artisan queue:work` |
| Duplicates | Check database constraints in migration |
| No reminders | Verify contract end_date is within 4-30 days |
| Wrong time | Set timezone: `APP_TIMEZONE=UTC` in `.env` |
| Mail errors | Test: `php artisan tinker` then `Mail::raw('test', ...)` |

---

## 📧 Email Details

```
RECIPIENT:     Company email (from $company->email)
SUBJECT:       "Contract Expiry Reminder: X days remaining"
FORMAT:        Professional HTML
TEMPLATE:      resources/views/emails/contract_expiry_reminder.blade.php
INCLUDES:      Contract details, dates, status, recommendations
RESPONSIVE:    Yes (mobile-friendly)
```

---

## 🗄️ Database Schema

```sql
contract_notification_logs TABLE
├─ id: BIGINT (Primary Key)
├─ contract_id: BIGINT (Foreign Key → contracts.id)
├─ days_remaining: INT (4, 7, 15, or 30)
├─ sent_at: TIMESTAMP
├─ created_at: TIMESTAMP
├─ updated_at: TIMESTAMP
└─ UNIQUE(contract_id, days_remaining, sent_at)
```

---

## 🎯 Reminder Schedule

For contract ending **2026-02-15**:

| Date | Days | Email |
|------|------|-------|
| 2026-01-16 | 30 | Sent ✉️ |
| 2026-01-31 | 15 | Sent ✉️ |
| 2026-02-08 | 7 | Sent ⚠️ |
| 2026-02-11 | 4 | Sent 🔴 |
| 2026-02-15 | 0 | Expired 📋 |

---

## 💾 Configuration Examples

### Gmail SMTP
```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Your Company"
```

### Queue Database
```env
QUEUE_CONNECTION=database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=your_database
DB_USERNAME=root
DB_PASSWORD=
```

---

## 📚 Documentation Guide

| Document | Purpose | Read Time |
|----------|---------|-----------|
| `SETUP_GUIDE.md` | Quick start | 5 min |
| `CONTRACT_EXPIRY_FEATURE.md` | Complete docs | 15 min |
| `FILE_STRUCTURE_OVERVIEW.md` | Architecture | 10 min |
| `VISUAL_GUIDE.md` | Diagrams | 10 min |
| `IMPLEMENTATION_CHECKLIST.md` | Verification | 20 min |

---

## 🔐 Security Checklist

- [x] Unique constraints prevent duplicates
- [x] All notifications logged
- [x] Status filtering (skip terminated)
- [x] Email validation
- [x] Queue isolation
- [x] Error handling
- [x] No sensitive data in logs

---

## 📈 Monitoring

### Daily Check
```bash
tail -f storage/logs/laravel.log
```

### Weekly Check
```sql
SELECT COUNT(*) FROM contract_notification_logs 
WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 7 DAY);
```

### Health Check
```bash
php artisan queue:failed
php artisan schedule:list
```

---

## 🎓 Key Concepts

**Mailable** - Formats emails
**Queue Job** - Async email delivery
**Artisan Command** - Custom CLI command
**Scheduler** - Runs command on schedule
**Migration** - Creates database table
**Logging** - Prevents duplicates and audits

---

## 🚀 Production Checklist

- [ ] Migration run: `php artisan migrate`
- [ ] `.env` configured with mail settings
- [ ] `.env` queue connection set
- [ ] Queue worker running: `php artisan queue:work`
- [ ] Cron job configured (scheduler)
- [ ] Test command run: `php artisan contracts:check-expiry`
- [ ] Email received in test
- [ ] Logs checked for errors

---

## 📞 Quick Support

**For Setup:** See `SETUP_GUIDE.md`
**For Issues:** See `CONTRACT_EXPIRY_FEATURE.md` → Troubleshooting
**For Architecture:** See `VISUAL_GUIDE.md`
**For Verification:** See `IMPLEMENTATION_CHECKLIST.md`

---

## ⏱️ Timeline

| Step | Time | Command |
|------|------|---------|
| Migration | 1 min | `php artisan migrate` |
| Config | 2 min | Edit `.env` |
| Queue | 1 min | `php artisan queue:work` |
| Test | 2 min | `php artisan contracts:check-expiry` |
| **Total** | **6 min** | Ready to go! |

---

## 🎉 Success! You now have:

✅ Automated daily contract checks
✅ Professional email reminders
✅ 30, 15, 7, 4-day notification schedule
✅ Duplicate prevention
✅ Complete audit trail
✅ Zero manual work required
✅ Production-ready system

---

**Save this card for quick reference!** 💾

**Start deployment:** `SETUP_GUIDE.md` 🚀
