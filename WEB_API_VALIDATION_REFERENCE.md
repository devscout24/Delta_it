# Web API Validation Rules & Request Bodies

**Base URL:** `http://your-domain/api/`  
**Authentication:** All endpoints require `Authorization: Bearer <token>` header

---

## Table of Contents
1. [Room Management](#room-management)
2. [Companies](#companies)
3. [Company Payments](#company-payments)
4. [Collaborators](#collaborators)
5. [Contracts](#contracts)
6. [Documents](#documents)
7. [Company Users](#company-users)
8. [Requests/Tickets](#requeststickets)
9. [Access Cards](#access-cards)
10. [Company Notes](#company-notes)
11. [Admin Payments](#admin-payments)
12. [Admin Contracts](#admin-contracts)
13. [Admin Tickets](#admin-tickets)
14. [Admin Documents](#admin-documents)
15. [Room Management (Spaces)](#room-management-spaces)
16. [Meeting Events](#meeting-events)
17. [User Management](#user-management)

---

## Room Management

### POST /web/map/rooms
**Create Room**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| floor | string | required | Floor identifier |
| room_name | string | required | Name of the room |
| area | numeric | required | Room area size |
| polygon_points | array | required | Array of coordinates as flat array [x1, y1, x2, y2, ...] |

**Example:**
```json
{
  "floor": "1",
  "room_name": "Conference Room A",
  "area": 50.5,
  "polygon_points": [0, 0, 100, 0, 100, 50, 0, 50]
}
```

---

### POST /web/map/rooms/assign-company
**Assign Company to Room**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| room_id | integer | required, exists:rooms,id | Room ID |
| company_id | integer | required, exists:companies,id | Company ID |

**Example:**
```json
{
  "room_id": 1,
  "company_id": 5
}
```

---

### POST /web/map/rooms/remove-company
**Remove Company from Room**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| room_id | integer | required, exists:rooms,id | Room ID |

**Example:**
```json
{
  "room_id": 1
}
```

---

## Companies

### POST /web/companies
**Create Company**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| name | string | required, max:255 | Company name |
| email | string | required, email, unique:companies,email | Company email |

**Example:**
```json
{
  "name": "Tech Corp Ltd",
  "email": "info@techcorp.com"
}
```

---

### PUT /web/companies/{id}
**Update Company**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| name | string | required, max:255 | Company name |
| email | string | required, email, unique:companies,email,{id} | Company email |
| fiscal_name | string | nullable | Legal/fiscal name |
| nif | string | nullable | Tax identification number |
| phone | string | nullable | Company phone |
| incubation_type | string | nullable | Type of incubation |
| business_area | mixed | nullable | Business area classification |
| manager | string | nullable | Manager name |
| description | string | nullable | Company description |

**Example:**
```json
{
  "name": "Tech Corp Ltd",
  "email": "info@techcorp.com",
  "fiscal_name": "Tech Corporation Limited",
  "nif": "12345678",
  "phone": "+1234567890",
  "incubation_type": "startup",
  "business_area": "Technology",
  "manager": "John Doe",
  "description": "Leading tech solutions provider"
}
```

---

## Company Payments

### GET /web/company-payments
**List Payments**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| company_id | integer | required | Company ID |
| year | integer | required | Year |

**Query String Example:** `?company_id=1&year=2025`

---

### POST /web/company-payments/init
**Initialize Year (Create 12 Months)**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| company_id | integer | required | Company ID |
| year | integer | required | Year to initialize |

**Example:**
```json
{
  "company_id": 1,
  "year": 2025
}
```

---

### PUT /web/company-payments/{id}
**Update Payment Month**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| value_non_vat | numeric | optional | Value without VAT |
| value_vat | numeric | optional | Value with VAT |
| printings_non_vat | numeric | optional | Printing costs without VAT |
| printings_vat | numeric | optional | Printing costs with VAT |
| status | string | optional | Payment status |

**Example:**
```json
{
  "value_non_vat": 1000.00,
  "value_vat": 210.00,
  "printings_non_vat": 50.00,
  "printings_vat": 10.50,
  "status": "paid"
}
```

---

### GET /web/company-payments/summary
**Payment Summary**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| company_id | integer | required | Company ID |
| year | integer | required | Year |

**Query String Example:** `?company_id=1&year=2025`

---

## Collaborators

### POST /web/collaborators
**Create Collaborator**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| company_id | integer | required, exists:companies,id | Company ID |
| first_name | string | required, max:255 | First name |
| last_name | string | required, max:255 | Last name |
| job_position | string | nullable, max:255 | Job position |
| email | string | nullable, email, unique:collaborators,email | Email address |
| phone_extension | string | nullable, max:20 | Phone extension |
| phone_number | string | nullable, max:20 | Phone number |
| access_card_number | string | nullable, max:50 | Access card number |
| parking_card | boolean | nullable | Has parking card |

**Example:**
```json
{
  "company_id": 1,
  "first_name": "John",
  "last_name": "Smith",
  "job_position": "Senior Developer",
  "email": "john.smith@company.com",
  "phone_extension": "1234",
  "phone_number": "+1-555-0001",
  "access_card_number": "AC-001",
  "parking_card": true
}
```

---

### PUT /web/collaborators/{id}
**Update Collaborator**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| first_name | string | required, max:255 | First name |
| last_name | string | required, max:255 | Last name |
| job_position | string | nullable, max:255 | Job position |
| email | string | nullable, email, unique:collaborators,email,{id} | Email address |
| phone_extension | string | nullable, max:20 | Phone extension |
| phone_number | string | nullable, max:20 | Phone number |
| access_card_number | string | nullable, max:50 | Access card number |
| parking_card | boolean | nullable | Has parking card |

**Example:**
```json
{
  "first_name": "John",
  "last_name": "Smith",
  "job_position": "Tech Lead",
  "email": "john.smith@company.com",
  "phone_extension": "1234",
  "phone_number": "+1-555-0001",
  "access_card_number": "AC-001",
  "parking_card": false
}
```

---

## Contracts

### PUT /web/contracts/{company_id}
**Update Contract**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| name | string | required | Contract name |
| type | string | required | Contract type |
| start_date | date | required | Start date (YYYY-MM-DD) |
| end_date | date | nullable | End date (YYYY-MM-DD) |
| renewal_date | date | nullable | Renewal date (YYYY-MM-DD) |
| status | string | nullable, in:active,inactive,terminated | Contract status |

**Example:**
```json
{
  "name": "Standard Service Agreement",
  "type": "SLA",
  "start_date": "2025-01-01",
  "end_date": "2026-01-01",
  "renewal_date": "2025-12-01",
  "status": "active"
}
```

---

### POST /web/contracts/files
**Upload Contract File**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| contract_id | integer | required, exists:contracts,id | Contract ID |
| file | file | required, max:4096 (4MB) | Contract file |

**Note:** Use `multipart/form-data` for file upload

**Example (cURL):**
```bash
curl -X POST /web/contracts/files \
  -F "contract_id=1" \
  -F "file=@contract.pdf"
```

---

## Documents

### POST /web/documents/{company_id}
**Upload Document**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| file | file | required, max:4096 (4MB) | Document file |
| tags | array | nullable | Array of tag strings |

**Example (Form Data):**
```json
{
  "file": "<file binary>",
  "tags": ["invoice", "2025"]
}
```

---

## Company Users

### POST /web/company-users
**Create Company User**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| company_id | integer | required, exists:companies,id | Company ID |
| first_name | string | required, max:100 | First name |
| last_name | string | required, max:100 | Last name |
| email | string | required, email, unique:users,email | Email address |
| password | string | required, min:6, confirmed | Password (must match password_confirmation) |
| job_position | string | nullable, max:100 | Job position |

**Example:**
```json
{
  "company_id": 1,
  "first_name": "Jane",
  "last_name": "Doe",
  "email": "jane.doe@company.com",
  "password": "SecurePass123",
  "password_confirmation": "SecurePass123",
  "job_position": "Manager"
}
```

---

### PUT /web/company-users/{id}
**Update Company User**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| first_name | string | required, max:100 | First name |
| last_name | string | required, max:100 | Last name |
| job_position | string | nullable, max:100 | Job position |

**Example:**
```json
{
  "first_name": "Jane",
  "last_name": "Doe",
  "job_position": "Senior Manager"
}
```

---

## Requests/Tickets

### POST /web/requests/{id}/reply
**Reply to Request**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| message | string | required | Reply message |

**Example:**
```json
{
  "message": "Your request has been processed successfully."
}
```

---

### POST /web/requests/{id}/updateStatus
**Update Request Status**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| status | string | required, in:pending,in_progress,resolved,closed | New status |

**Example:**
```json
{
  "status": "in_progress"
}
```

---

## Access Cards

### PUT /web/access-cards/{company_id}
**Update Access Card**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| active_card | integer | nullable | Number of active cards |
| lost_damage_card | integer | nullable | Number of lost/damaged cards |
| active_parking_card | integer | nullable | Number of active parking cards |
| max_parking_card | integer | nullable | Maximum parking cards allowed |

**Example:**
```json
{
  "active_card": 5,
  "lost_damage_card": 1,
  "active_parking_card": 3,
  "max_parking_card": 5
}
```

---

## Company Notes

### POST /web/company-notes
**Create Company Note**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| company_id | integer | required, exists:companies,id | Company ID |
| note | string | required | Note content |

**Example:**
```json
{
  "company_id": 1,
  "note": "Follow up regarding contract renewal next month"
}
```

---

## Admin Payments

### GET /web/payments
**List All Payments**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| month | integer | required, min:1, max:12 | Month (1-12) |
| year | integer | required | Year |
| search | string | nullable | Search company name |

**Query String Example:** `?month=3&year=2025&search=Tech`

---

### GET /web/payments/summary
**Payment Summary**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| month | integer | required, min:1, max:12 | Month (1-12) |
| year | integer | required | Year |

**Query String Example:** `?month=3&year=2025`

---

## Admin Contracts

### POST /web/admin/contracts
**Create Contract**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| company_id | integer | required, exists:companies,id | Company ID |
| name | string | required | Contract name |
| type | string | required | Contract type |
| start_date | date | required | Start date (YYYY-MM-DD) |
| end_date | date | nullable | End date (YYYY-MM-DD) |
| renewal_date | date | nullable | Renewal date (YYYY-MM-DD) |
| status | string | required, in:active,terminated | Contract status |

**Example:**
```json
{
  "company_id": 1,
  "name": "Service Agreement 2025",
  "type": "SLA",
  "start_date": "2025-01-01",
  "end_date": "2026-01-01",
  "renewal_date": "2025-12-01",
  "status": "active"
}
```

---

### PUT /web/admin/contracts/{id}
**Update Contract**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| company_id | integer | optional, exists:companies,id | Company ID |
| name | string | optional | Contract name |
| type | string | optional | Contract type |
| start_date | date | optional | Start date (YYYY-MM-DD) |
| end_date | date | nullable | End date (YYYY-MM-DD) |
| renewal_date | date | nullable | Renewal date (YYYY-MM-DD) |
| status | string | optional | Contract status |

**Example:**
```json
{
  "name": "Updated Service Agreement",
  "status": "active"
}
```

---

### POST /web/admin/contracts/files
**Upload Contract File**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| contract_id | integer | required, exists:contracts,id | Contract ID |
| file | file | required, max:4096 (4MB) | File to upload |

---

## Admin Tickets

### POST /web/admin/tickets
**Create Ticket**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| company_id | integer | required, exists:companies,id | Company ID |
| subject | string | required | Ticket subject |
| type | string | required | Ticket type |
| message | string | required | Initial message |

**Example:**
```json
{
  "company_id": 1,
  "subject": "Network connectivity issue",
  "type": "technical",
  "message": "The company is experiencing network issues in the office"
}
```

---

### POST /web/admin/tickets/{id}/reply
**Reply to Ticket**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| message | string | required | Reply message |

**Example:**
```json
{
  "message": "We have escalated this to the network team. Expected resolution in 2 hours."
}
```

---

### POST /web/admin/tickets/{id}/status
**Update Ticket Status**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| status | string | required, in:pending,in_progress,resolved,closed | New status |

**Example:**
```json
{
  "status": "resolved"
}
```

---

## Admin Documents

### POST /web/admin/documents
**Upload Document**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| file | file | required, max:4096 (4MB) | Document file |
| company_id | integer | nullable, exists:companies,id | Company ID |
| tags | array | nullable | Array of tag names |

**Example:**
```json
{
  "file": "<file binary>",
  "company_id": 1,
  "tags": ["invoice", "paid", "2025"]
}
```

---

### PUT /web/admin/documents/{id}
**Update Document Tags**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| tags | array | optional | Array of tag names |

**Example:**
```json
{
  "tags": ["invoice", "processed", "archived"]
}
```

---

## Room Management (Spaces)

### POST /web/admin/rooms
**Create Space/Room**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| name | string | required | Space name |
| capacity | integer | nullable | Maximum capacity |
| color | string | nullable | Display color |
| description | string | nullable | Space description |

**Example:**
```json
{
  "name": "Main Conference Room",
  "capacity": 50,
  "color": "#FF6B6B",
  "description": "Large conference room with video conferencing"
}
```

---

### PUT /web/admin/rooms/{id}
**Update Space/Room**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| name | string | optional | Space name |
| capacity | integer | nullable | Maximum capacity |
| color | string | nullable | Display color |
| description | string | nullable | Space description |

**Example:**
```json
{
  "name": "Main Conference Room",
  "capacity": 55
}
```

---

### POST /web/admin/rooms/{id}/schedule
**Add Schedule to Space**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| start_date | date | required | Schedule start date (YYYY-MM-DD) |
| end_date | date | required, after_or_equal:start_date | Schedule end date |
| duration | integer | required, min:5 | Slot duration in minutes |
| days | array | required | Array of day objects |
| days[].day | string | required | Day name (Monday, Tuesday, etc.) |
| days[].start_time | time | required | Start time (HH:MM) |
| days[].end_time | time | required | End time (HH:MM) |

**Example:**
```json
{
  "start_date": "2025-03-01",
  "end_date": "2025-03-31",
  "duration": 30,
  "days": [
    {
      "day": "Monday",
      "start_time": "09:00",
      "end_time": "17:00"
    },
    {
      "day": "Tuesday",
      "start_time": "09:00",
      "end_time": "17:00"
    }
  ]
}
```

---

### GET /web/admin/rooms/{id}/slots
**Get Space Slots**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| date | date | optional | Filter by date (YYYY-MM-DD) |

**Query String Example:** `?date=2025-03-15`

---

## Meeting Events

### POST /web/meeting-events
**Create Meeting Event**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| title | string | required | Event title |
| type | string | required | Event type |
| duration | integer | required | Duration in minutes |
| location | string | nullable | Event location |
| meeting_link | string | nullable | Virtual meeting link (URL) |
| max_invites | integer | nullable | Maximum invitees |
| description | string | nullable | Event description |
| color | string | nullable | Display color |

**Example:**
```json
{
  "title": "Weekly Standup",
  "type": "internal_meeting",
  "duration": 30,
  "location": "Conference Room A",
  "meeting_link": "https://meet.example.com/standup",
  "max_invites": 20,
  "description": "Weekly team synchronization meeting",
  "color": "#4ECDC4"
}
```

---

### PUT /web/meeting-events/{id}
**Update Meeting Event**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| title | string | optional | Event title |
| type | string | optional | Event type |
| duration | integer | optional | Duration in minutes |
| location | string | nullable | Event location |
| meeting_link | string | nullable | Virtual meeting link |
| max_invites | integer | nullable | Maximum invitees |
| description | string | nullable | Event description |
| color | string | nullable | Display color |

**Example:**
```json
{
  "title": "Weekly Standup - Updated",
  "max_invites": 25
}
```

---

### POST /web/meeting-events/{id}/schedule
**Add Schedule to Meeting Event**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| start_date | date | required | Schedule start date (YYYY-MM-DD) |
| end_date | date | required, after_or_equal:start_date | Schedule end date |
| days | array | required | Array of day objects |
| days[].day | string | required | Day name (Monday, Tuesday, etc.) |
| days[].start_time | time | required | Start time (HH:MM) |
| days[].end_time | time | required | End time (HH:MM) |

**Example:**
```json
{
  "start_date": "2025-03-01",
  "end_date": "2025-03-31",
  "days": [
    {
      "day": "Monday",
      "start_time": "10:00",
      "end_time": "11:00"
    },
    {
      "day": "Wednesday",
      "start_time": "10:00",
      "end_time": "11:00"
    }
  ]
}
```

---

### GET /web/meeting-events/{id}/slots
**Get Meeting Event Slots**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| date | date | required | Date to fetch slots for (YYYY-MM-DD) |

**Query String Example:** `?date=2025-03-15`

---

### POST /web/meeting-events/requests/{id}/approve
**Approve Meeting Booking**

No request body required. Approves a pending booking request.

---

### POST /web/meeting-events/requests/{id}/reject
**Reject Meeting Booking**

No request body required. Rejects a pending booking request.

---

## User Management

### POST /web/users
**Create User**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| name | string | required, max:100 | User full name |
| email | string | required, email, unique:users,email | Email address |
| password | string | required, min:6 | Password |
| role | string | required, in:admin,company | User role |
| company_id | integer | nullable, exists:companies,id | Company ID (null for admin) |

**Important:** If role is `admin`, company_id must be null/omitted.

**Example:**
```json
{
  "name": "Admin User",
  "email": "admin@example.com",
  "password": "AdminPass123",
  "role": "admin"
}
```

**Company User Example:**
```json
{
  "name": "Company Manager",
  "email": "manager@company.com",
  "password": "CompanyPass123",
  "role": "company",
  "company_id": 1
}
```

---

### PUT /web/users/{id}
**Update User**

| Field | Type | Rules | Notes |
|-------|------|-------|-------|
| name | string | nullable, max:100 | User full name |
| email | string | nullable, email, unique:users,email,{id} | Email address |
| password | string | nullable, min:6 | New password |
| role | string | nullable, in:admin,company | User role |
| company_id | integer | nullable, exists:companies,id | Company ID |

**Important:** If role is changed to `admin`, company_id will be automatically set to null.

**Example:**
```json
{
  "name": "Updated Name",
  "password": "NewSecurePass123"
}
```

---

## Common Response Format

All endpoints return responses in this format:

**Success (200-201):**
```json
{
  "success": true,
  "data": { /* endpoint-specific data */ },
  "message": "Success message"
}
```

**Error (422+):**
```json
{
  "success": false,
  "error": { /* validation errors or error details */ },
  "message": "Error message"
}
```

---

## Authentication

All endpoints require the `Authorization` header:

```
Authorization: Bearer <your_jwt_token>
```

---

## File Upload Notes

For file upload endpoints:
- **Content-Type:** `multipart/form-data`
- **Max File Size:** 4MB (4096 KB)
- **Supported:** Any file type (PDF, DOC, XLS, etc.)

---

## Date Format

All dates should be in `YYYY-MM-DD` format:
- Example: `2025-03-15`

## Time Format

Times should be in `HH:MM` format (24-hour):
- Example: `14:30` (2:30 PM)

---

## Pagination

List endpoints return paginated results:

```json
{
  "success": true,
  "data": {
    "items": [...],
    "pagination": {
      "current_page": 1,
      "last_page": 5,
      "total": 48
    }
  }
}
```

---

## Query Parameters for Filtering

Most GET endpoints support:
- `search` - Search by name/subject
- `status` - Filter by status
- `company_id` - Filter by company
- `role` - Filter by role (users)
- `type` - Filter by type
- `date` - Filter by date

Example: `GET /web/companies?search=Tech&type=archived`

