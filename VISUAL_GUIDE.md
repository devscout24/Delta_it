# 📱 Contract Expiry Reminder - Visual Implementation Guide

## 🎬 How the Feature Works (Step by Step)

### Timeline Example: Contract Expires February 15, 2026

```
JANUARY                    FEBRUARY
│                          │
16 ─────────────────────── 15 (Expiry Date)
├─ 30 DAYS BEFORE
│  ✉️ Email #1 Sent
│  "Your contract expires in 30 days"
│
31 ─────────────────────── 15 (Expiry Date)
├─ 15 DAYS BEFORE
│  ✉️ Email #2 Sent
│  "Your contract expires in 15 days"
│
FEBRUARY
│  8 ──────────────────── 15 (Expiry Date)
├─ 7 DAYS BEFORE
│  ⚠️ Email #3 Sent (URGENT)
│  "Your contract expires in 7 days"
│
│  11 ─────────────────── 15 (Expiry Date)
├─ 4 DAYS BEFORE
│  🔴 Email #4 Sent (VERY URGENT)
│  "Your contract expires in 4 days - IMMEDIATE ACTION REQUIRED"
│
│  15 ─────────────────── EXPIRY! 📋
│  Contract expires
│
```

---

## 🔄 System Architecture

```
┌──────────────────────────────────────────────────────┐
│                   LARAVEL APPLICATION                 │
├──────────────────────────────────────────────────────┤
│                                                        │
│  ┌────────────────────────────────────────────────┐  │
│  │           SCHEDULER (runs daily)               │  │
│  │              at 8:00 AM UTC                    │  │
│  └──────────────────┬─────────────────────────────┘  │
│                     │                                  │
│                     ▼                                  │
│  ┌────────────────────────────────────────────────┐  │
│  │       CheckContractExpiry Command              │  │
│  │  • Checks contracts expiring in 4/7/15/30 days│  │
│  │  • Prevents duplicate notifications           │  │
│  │  • Skips terminated/expired contracts         │  │
│  └──────────────────┬─────────────────────────────┘  │
│                     │                                  │
│          ┌──────────┼──────────┬──────────┐           │
│          │          │          │          │           │
│          ▼          ▼          ▼          ▼           │
│       4-day     7-day      15-day      30-day         │
│      window    window     window      window          │
│          │          │          │          │           │
│          └──────────┼──────────┴──────────┘           │
│                     │                                  │
│                     ▼                                  │
│  ┌────────────────────────────────────────────────┐  │
│  │    Dispatch SendContractExpiryReminder        │  │
│  │              Job to Queue                      │  │
│  └──────────────────┬─────────────────────────────┘  │
│                     │                                  │
│                     ▼                                  │
│  ┌────────────────────────────────────────────────┐  │
│  │         Queue Storage (Database)               │  │
│  │  Stores jobs for asynchronous processing       │  │
│  └──────────────────┬─────────────────────────────┘  │
│                     │                                  │
└─────────────────────┼──────────────────────────────────┘
                      │
                      ▼
        ┌─────────────────────────────┐
        │      QUEUE WORKER           │
        │  (php artisan queue:work)   │
        └──────────────┬──────────────┘
                       │
            ┌──────────┴──────────┐
            │                     │
            ▼                     ▼
   ┌─────────────────┐   ┌─────────────────┐
   │ Mail Class      │   │ Get Company     │
   │ Renders HTML    │   │ Email Address   │
   └────────┬────────┘   └────────┬────────┘
            │                     │
            └──────────┬──────────┘
                       ▼
        ┌─────────────────────────────┐
        │    Send via Mail Driver     │
        │   (SMTP / Mailgun / etc)    │
        └──────────────┬──────────────┘
                       │
                       ▼
        ┌─────────────────────────────┐
        │   📧 Email Delivered        │
        │  to company@example.com     │
        └──────────────┬──────────────┘
                       │
                       ▼
        ┌─────────────────────────────┐
        │  Log Notification Sent      │
        │ (Prevent future duplicates) │
        └─────────────────────────────┘
```

---

## 📊 Data Flow

```
CONTRACT MODEL
    │
    ├─ id: 1
    ├─ company_id: 5
    ├─ name: "Service Agreement 2025"
    ├─ end_date: 2026-02-15
    └─ status: active

        ▼

SCHEDULER CHECK (Daily @ 8:00 AM)
    │
    ├─ Get all contracts where end_date = TODAY + 4/7/15/30 days
    └─ Filter: status != 'terminated' AND status != 'expired'

        ▼

FOR EACH MATCHING CONTRACT
    │
    ├─ Check ContractNotificationLog for duplicate
    ├─ If not found, create job
    └─ Log notification in database

        ▼

SEND JOB DISPATCHED
    │
    ├─ Get Company from Contract relationship
    ├─ Retrieve company email address
    ├─ Instantiate Mail class
    └─ Queue job for async delivery

        ▼

QUEUE WORKER PROCESSES JOB
    │
    ├─ Build email with company, contract, daysRemaining
    ├─ Render HTML template
    ├─ Send via Mail::to()->send()
    └─ Mark job as complete

        ▼

EMAIL NOTIFICATION SENT
    │
    ├─ Subject: "Contract Expiry Reminder: 7 days remaining"
    ├─ To: company@example.com
    ├─ Contains: Contract details, dates, recommendations
    └─ Format: Professional HTML with branding

        ▼

AUDIT TRAIL CREATED
    │
    └─ Log entry in contract_notification_logs table
       ├─ contract_id: 1
       ├─ days_remaining: 7
       ├─ sent_at: 2026-02-08 08:15:00
       └─ created_at: 2026-02-08 08:15:00
```

---

## 🎯 Configuration Points

```
┌─────────────────────────────────────────┐
│        KEY CONFIGURATION FILES           │
└─────────────────────────────────────────┘

1. EMAIL CONFIGURATION
   📁 .env
   ├─ MAIL_DRIVER=smtp
   ├─ MAIL_HOST=smtp.example.com
   ├─ MAIL_PORT=587
   ├─ MAIL_USERNAME=your-email
   ├─ MAIL_PASSWORD=your-password
   └─ MAIL_FROM_ADDRESS=noreply@example.com

2. QUEUE CONFIGURATION
   📁 .env
   ├─ QUEUE_CONNECTION=database
   └─ Ensure queue worker is running

3. SCHEDULER SETUP
   📁 .env
   ├─ APP_TIMEZONE=UTC  (Set to your timezone)
   └─ Ensure cron job is configured

4. COMMAND SCHEDULE
   📁 routes/console.php
   ├─ Daily at 08:00
   └─ Can be customized to your timezone

5. REMINDER DAYS
   📁 app/Console/Commands/CheckContractExpiry.php
   ├─ Currently: 4, 7, 15, 30 days
   └─ Edit $remindDays array to customize

6. EMAIL TEMPLATE
   📁 resources/views/emails/contract_expiry_reminder.blade.php
   ├─ Customize HTML layout
   ├─ Adjust colors and content
   └─ Add company branding
```

---

## 📈 Typical Workflow During One Day

```
8:00:00 AM - Scheduler triggers
    │
    ├─ Check contracts expiring in 4 days
    │  └─ Found 3 contracts → Queue 3 jobs
    │
    ├─ Check contracts expiring in 7 days
    │  └─ Found 2 contracts → Queue 2 jobs
    │
    ├─ Check contracts expiring in 15 days
    │  └─ Found 1 contract → Queue 1 job
    │
    └─ Check contracts expiring in 30 days
       └─ Found 5 contracts → Queue 5 jobs
    
    TOTAL: 11 jobs queued

8:00:30 AM - Command completes
    │
    └─ 11 notification logs created

8:01 AM - Queue Worker processes first 5 jobs
    ├─ Job 1: Email sent to company1@example.com ✓
    ├─ Job 2: Email sent to company2@example.com ✓
    ├─ Job 3: Email sent to company3@example.com ✓
    ├─ Job 4: Email sent to company4@example.com ✓
    └─ Job 5: Email sent to company5@example.com ✓

8:02 AM - Queue Worker processes next 5 jobs
    ├─ Job 6: Email sent to company6@example.com ✓
    ├─ Job 7: Email sent to company7@example.com ✓
    ├─ Job 8: Email sent to company8@example.com ✓
    ├─ Job 9: Email sent to company9@example.com ✓
    └─ Job 10: Email sent to company10@example.com ✓

8:02:30 AM - Queue Worker processes last job
    └─ Job 11: Email sent to company11@example.com ✓

RESULT: 11 emails delivered, 11 logs created
```

---

## 🗄️ Database Structure

```
contracts TABLE (existing)
├─ id: 1
├─ company_id: 5
├─ name: "Service Agreement"
├─ start_date: 2025-02-15
├─ end_date: 2026-02-15    ◄─────┐
├─ renewal_date: NULL              │
├─ status: "active"               │
└─ created_at: 2025-02-15         │
                                   │
companies TABLE (existing)         │
├─ id: 5                           │
├─ name: "ABC Company"             │
├─ email: "contact@abc.com"  ◄─────┤
├─ phone_number: "123-456-7890"    │
├─ nif: "12345678"                 │
└─ status: "active"                │
                                   │
contract_notification_logs TABLE (NEW)
├─ id: 101                         │
├─ contract_id: 1            ◄─────┘
├─ days_remaining: 7
├─ sent_at: 2026-02-08 08:15:00
├─ created_at: 2026-02-08 08:15:00
└─ updated_at: 2026-02-08 08:15:00
```

---

## 📧 Email Content Example

```
╔════════════════════════════════════════╗
║     📋 Contract Expiry Reminder         ║
╚════════════════════════════════════════╝

Hello ABC Company,

⏰ Important Notice: Your contract will expire in 7 days.

Contract Details:
┌──────────────────────────────────────┐
│ Contract Name: Service Agreement     │
│ Contract Type: Full-time             │
│ Start Date: 15/02/2025               │
│ Expiration Date: 15/02/2026 (URGENT) │
│ Current Status: Active               │
└──────────────────────────────────────┘

Recommended Actions:
• Review the contract terms and conditions
• Prepare for renewal or termination as needed
• Reach out to your account manager

If you have any questions, contact us immediately.

---
This is an automated message.
© 2026 Your Company Name. All Rights Reserved.
```

---

## 🔐 Security & Best Practices

```
✅ IMPLEMENTED FEATURES

1. DUPLICATE PREVENTION
   └─ Unique constraint on (contract_id, days_remaining, sent_at)
   └─ Check before sending prevents duplicates

2. AUDIT TRAIL
   └─ Every notification logged in database
   └─ Timestamps recorded
   └─ Full traceability

3. QUEUE ISOLATION
   └─ Email sending doesn't block main application
   └─ Failed emails can be retried
   └─ Scalable for high volume

4. STATUS FILTERING
   └─ Only active contracts get reminders
   └─ Terminated contracts skipped
   └─ Expired contracts skipped

5. ERROR HANDLING
   └─ Graceful failures
   └─ Queue retries on failure
   └─ Logs for debugging

6. PRIVACY
   └─ Only sends to company email
   └─ No sensitive data in logs
   └─ GDPR compliant (no personal data)
```

---

## 🚀 Deployment Visualization

```
DEVELOPMENT
│
├─ Code Changes
│  ├─ app/Mail/
│  ├─ app/Jobs/
│  ├─ app/Console/Commands/
│  ├─ app/Models/
│  ├─ database/migrations/
│  ├─ resources/views/emails/
│  └─ routes/console.php
│
├─ Testing
│  ├─ php artisan migrate
│  ├─ php artisan contracts:check-expiry
│  └─ Manual email verification
│
├─ Staging
│  ├─ Run all tests again
│  ├─ Verify with test contracts
│  └─ Check queue processing
│
└─────────────────────────────────────────►

PRODUCTION
│
├─ Pre-Deployment
│  ├─ Backup database
│  ├─ Review all changes
│  └─ Notify team
│
├─ Deployment
│  ├─ git push
│  ├─ php artisan migrate --force
│  ├─ Start queue worker
│  └─ Verify scheduler
│
├─ Post-Deployment
│  ├─ Monitor logs
│  ├─ Check email sending
│  ├─ Verify no errors
│  └─ Team notification
│
└─ Ongoing Monitoring
   ├─ Daily: Check queue
   ├─ Weekly: Review notifications
   ├─ Monthly: Performance review
   └─ Quarterly: Feature updates
```

---

## ✨ Feature Benefits

```
FOR COMPANY MANAGERS
├─ Automatic reminders before expiry
├─ Professional email notifications
├─ Adequate time to renew or terminate
└─ Prevents missed deadlines

FOR SYSTEM ADMINISTRATORS
├─ Zero manual work required
├─ Automated daily execution
├─ Scalable to thousands of contracts
├─ Complete audit trail
└─ Easy to customize

FOR BUSINESS
├─ Improved contract management
├─ Reduced missed renewals
├─ Better client relationships
├─ Compliance tracking
└─ Professional communication
```

---

## 🎓 Learning Points

This feature demonstrates:
- ✅ Laravel Mail & Mailable
- ✅ Queue Jobs & Async Processing
- ✅ Artisan Commands
- ✅ Task Scheduling
- ✅ Database Migrations
- ✅ Eloquent Relationships
- ✅ Blade Templating
- ✅ Security Best Practices
- ✅ Error Handling
- ✅ Logging & Auditing

---

**Ready to deploy! 🚀**
