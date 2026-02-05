# 📦 Contract Expiry Reminder Feature - File Structure Overview

## Complete File Listing

### Core Components

```
app/
├── Mail/
│   └── ContractExpiryReminderMail.php ..................... Email formatter class
│
├── Jobs/
│   └── SendContractExpiryReminder.php ..................... Queued job for async sending
│
├── Console/
│   └── Commands/
│       └── CheckContractExpiry.php ........................ Artisan command (daily scheduler)
│
└── Models/
    └── ContractNotificationLog.php ........................ Tracks sent notifications

database/
└── migrations/
    └── 2026_02_05_000000_create_contract_notification_logs_table.php ... Database setup

resources/
└── views/
    └── emails/
        └── contract_expiry_reminder.blade.php ............ HTML email template

routes/
└── console.php (MODIFIED) ................................ Scheduler configuration
```

---

## File Descriptions

### 1️⃣ Mail Class
**File:** `app/Mail/ContractExpiryReminderMail.php` (1,014 bytes)

Formats the expiry reminder email with:
- Subject line showing days remaining
- Company and contract information
- Email template rendering

**Usage:**
```php
Mail::to($email)->send(new ContractExpiryReminderMail($company, $contract, $daysRemaining));
```

---

### 2️⃣ Queued Job
**File:** `app/Jobs/SendContractExpiryReminder.php` (1,089 bytes)

Handles asynchronous email delivery:
- Gets company email from contract relationship
- Dispatches mail via the queue
- Non-blocking execution
- Implements `ShouldQueue` interface

**Usage:**
```php
SendContractExpiryReminder::dispatch($contract, $daysRemaining);
```

---

### 3️⃣ Artisan Command
**File:** `app/Console/Commands/CheckContractExpiry.php` (2,205 bytes)

Daily task that:
- Scans all contracts for upcoming expirations
- Checks 4, 7, 15, and 30-day thresholds
- Prevents duplicate notifications
- Logs all actions
- Only processes active contracts

**Execution:**
```bash
php artisan contracts:check-expiry
```

---

### 4️⃣ Notification Log Model
**File:** `app/Models/ContractNotificationLog.php` (506 bytes)

Eloquent model for tracking:
- Which notifications were sent
- Days remaining when sent
- Timestamp of sending
- Relationship to Contract

**Database Access:**
```php
$logs = ContractNotificationLog::where('contract_id', $id)->get();
```

---

### 5️⃣ Database Migration
**File:** `database/migrations/2026_02_05_000000_create_contract_notification_logs_table.php` (958 bytes)

Creates `contract_notification_logs` table with columns:
- `id` - Primary key
- `contract_id` - Foreign key to contracts
- `days_remaining` - 4, 7, 15, or 30
- `sent_at` - When notification was sent
- `created_at`, `updated_at` - Timestamps
- Unique constraint to prevent duplicates

**Schema:**
```sql
CREATE TABLE contract_notification_logs (
  id BIGINT PRIMARY KEY,
  contract_id BIGINT,
  days_remaining INT,
  sent_at TIMESTAMP,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY(contract_id) REFERENCES contracts(id),
  UNIQUE(contract_id, days_remaining, sent_at)
);
```

---

### 6️⃣ Email Template
**File:** `resources/views/emails/contract_expiry_reminder.blade.php` (5,251 bytes)

Professional HTML email featuring:
- Header with title
- Alert box (color-coded by urgency)
- Contract details display
- Company information
- Recommended actions list
- Professional footer
- Responsive design
- CSS styling

**Variables Available:**
- `$company` - Company model
- `$contract` - Contract model
- `$daysRemaining` - Integer (4, 7, 15, or 30)

---

### 7️⃣ Scheduler Configuration
**File:** `routes/console.php` (MODIFIED, 486 bytes)

Added scheduler configuration:
```php
Schedule::command('contracts:check-expiry')
    ->dailyAt('08:00')
    ->description('Check for contracts expiring...');
```

Runs the check command every day at 8:00 AM.

---

## Data Flow Diagram

```
┌─────────────────────┐
│  Daily Scheduler    │
│   (8:00 AM)         │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ CheckContractExpiry │
│    Command          │
└──────────┬──────────┘
           │
     ┌─────┴─────┬─────────┬──────────┐
     │           │         │          │
     ▼           ▼         ▼          ▼
  30 days    15 days   7 days    4 days
   check      check     check     check
     │           │         │          │
     └─────┬─────┴────┬────┴─────┬───┘
           │          │          │
           ▼          ▼          ▼
   Contract with   Contract    Notification
   contract found  exists?  already sent?
           │           │          │
           ├───────────┴──────────┤
           │                      │
           ▼                      ▼
    Dispatch Job           Skip (already sent)
           │
           ▼
  SendContractExpiry
      Reminder Job
           │
           ▼
      Queue Worker
           │
           ▼
    Get Company Email
           │
           ▼
    Send HTML Email
           │
           ▼
    Log Notification
```

---

## Sequence of Operations

### When Command Runs

```
1. CheckContractExpiry command starts
2. Loop through reminder days: 4, 7, 15, 30
3. For each day:
   a. Calculate target date (today + X days)
   b. Find all contracts expiring on that date
   c. For each contract:
      - Check if already sent
      - If not sent:
        * Dispatch SendContractExpiryReminder job
        * Create log entry
4. Command completes
```

### When Job Processes (Queue Worker)

```
1. SendContractExpiryReminder job picked up
2. Get contract from database
3. Get company from contract relationship
4. Check company has valid email
5. Send email using ContractExpiryReminderMail
6. Email delivered via configured mail driver
7. Job marked as complete
```

### Email Sending

```
1. Mail class instantiated with company, contract, days
2. Subject generated: "Contract Expiry Reminder: X days remaining"
3. Template rendered with variables
4. Email sent to company->email
5. Log created for audit trail
```

---

## Integration with Existing System

### Uses These Existing Models
- `App\Models\Contract`
- `App\Models\Company`
- `App\Models\ContractFile`

### Uses These Existing Features
- Laravel Mail (configured in `config/mail.php`)
- Laravel Queue (configured in `config/queue.php`)
- Laravel Scheduler (via `routes/console.php`)
- Laravel Commands (Artisan)

### Database Relationships
```
Contract
  ├─ hasMany: ContractFile
  ├─ belongsTo: Company
  ├─ belongsToMany: Company (associates)
  └─ hasMany: ContractNotificationLog (new)

Company
  ├─ hasOne: Room
  ├─ hasMany: Room
  ├─ hasOne: Contract
  └─ hasMany: Contract

ContractNotificationLog (new)
  └─ belongsTo: Contract
```

---

## Environment Configuration Required

```env
# Mail Configuration
MAIL_DRIVER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Your Company"

# Queue Configuration
QUEUE_CONNECTION=database  # or redis, sync, etc.

# Application
APP_TIMEZONE=UTC  # Set to your timezone
```

---

## Total Implementation Size

| Component | Size | Lines |
|-----------|------|-------|
| ContractExpiryReminderMail.php | 1,014 B | ~28 |
| SendContractExpiryReminder.php | 1,089 B | ~31 |
| CheckContractExpiry.php | 2,205 B | ~59 |
| ContractNotificationLog.php | 506 B | ~23 |
| Migration file | 958 B | ~36 |
| Email template | 5,251 B | ~156 |
| console.php (modified) | 486 B | ~15 |
| **TOTAL** | **~11.5 KB** | **~348** |

---

## Ready to Deploy

✅ All files created
✅ Migration ready
✅ Configuration added
✅ Email template complete
✅ Documentation provided
✅ Zero breaking changes

**Next Steps:**
1. Run migration: `php artisan migrate`
2. Start queue: `php artisan queue:work`
3. Test command: `php artisan contracts:check-expiry`
4. Set up cron job for scheduler (production)
