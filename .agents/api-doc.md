# TicketRush API Documentation

> Source of truth for FE developers. Update this file whenever any API route, request payload, response shape, validation rule, auth rule, or business behavior changes.

## 1. API conventions

### Base URL

```txt
/api
```

### Headers

For public APIs:

```txt
Accept: application/json
Content-Type: application/json
```

For authenticated APIs:

```txt
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

### Standard success response

```json
{
  "success": true,
  "message": "Request processed successfully.",
  "data": {}
}
```

### Standard error response

```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {}
}
```

### Auth method

- API authentication uses Laravel Sanctum Bearer token.
- Login and email verification return token.
- FE must store token securely and send it in `Authorization` header.

### Roles

| Role | Description |
|---|---|
| `admin` | System administrator. Can manage/approve events. |
| `organizer` | Event organizer. Can create and manage own events. |
| `customer` | Event customer. Can browse events, book seats, and manage tickets. |

## 2. Auth APIs

### 2.1 Customer register

```txt
POST /api/auth/register/customer
```

Auth: Public

Request body:

```json
{
  "name": "Nguyen Van A",
  "email": "customer@example.com",
  "password": "password",
  "password_confirmation": "password",
  "gender": "male",
  "birthday": "2000-01-01"
}
```

Validation:

| Field | Required | Rule |
|---|---:|---|
| `name` | Yes | string, max 255 |
| `email` | Yes | valid email, unique in users |
| `password` | Yes | string, min 6, confirmed |
| `password_confirmation` | Yes | must match password |
| `gender` | No | `male`, `female`, `other` |
| `birthday` | No | date, before today |

Behavior:

- Creates user with `role = customer`.
- Sends 6-digit verification code to email.
- Account cannot login until email is verified.

Success response `201`:

```json
{
  "success": true,
  "message": "Registration successful. Please check your email for the verification code.",
  "data": {
    "user_id": 1,
    "email": "customer@example.com"
  }
}
```

### 2.2 Organizer register

```txt
POST /api/auth/register/organizer
```

Auth: Public

Request body:

```json
{
  "name": "Tran Van B",
  "email": "organizer@example.com",
  "password": "password",
  "password_confirmation": "password",
  "organizer_name": "ABC Event Company",
  "tax_code": "0123456789"
}
```

Validation:

| Field | Required | Rule |
|---|---:|---|
| `name` | Yes | string, max 255 |
| `email` | Yes | valid email, unique in users |
| `password` | Yes | string, min 6, confirmed |
| `password_confirmation` | Yes | must match password |
| `organizer_name` | Yes | string, max 255 |
| `tax_code` | Yes | string, max 50, unique in users |

Behavior:

- Creates user with `role = organizer`.
- Sends 6-digit verification code to email.
- Account cannot login until email is verified.

Success response `201`:

```json
{
  "success": true,
  "message": "Registration successful. Please check your email for the verification code.",
  "data": {
    "user_id": 2,
    "email": "organizer@example.com"
  }
}
```

### 2.3 Verify email

```txt
POST /api/auth/verify
```

Auth: Public

Request body:

```json
{
  "email": "customer@example.com",
  "code": "123456"
}
```

Validation:

| Field | Required | Rule |
|---|---:|---|
| `email` | Yes | valid email, exists in users |
| `code` | Yes | string, exactly 6 characters |

Behavior:

- Verifies the registration code.
- Sets `email_verified_at`.
- Deletes used verification code.
- Returns Sanctum token.

Success response:

```json
{
  "success": true,
  "message": "Email verified successfully.",
  "data": {
    "token": "1|plain-text-token",
    "user": {
      "id": 1,
      "name": "Nguyen Van A",
      "email": "customer@example.com",
      "role": "customer"
    }
  }
}
```

Error response `422`:

```json
{
  "success": false,
  "message": "Invalid or expired verification code."
}
```

### 2.4 Resend verification code

```txt
POST /api/auth/resend-code
```

Auth: Public

Request body:

```json
{
  "email": "customer@example.com"
}
```

Behavior:

- Deletes old registration verification codes for the email.
- Sends a new 6-digit verification code.
- Code expires after 15 minutes.

Success response:

```json
{
  "success": true,
  "message": "A new verification code has been sent to your email."
}
```

### 2.5 Login

```txt
POST /api/auth/login
```

Auth: Public

Request body:

```json
{
  "email": "customer@example.com",
  "password": "password"
}
```

Validation:

| Field | Required | Rule |
|---|---:|---|
| `email` | Yes | valid email |
| `password` | Yes | string |

Behavior:

- Checks email and password.
- Blocks login if email is not verified.
- If email is unverified, sends a new verification code.
- Returns Sanctum token if successful.

Success response:

```json
{
  "success": true,
  "message": "Login successful.",
  "data": {
    "token": "1|plain-text-token",
    "user": {
      "id": 1,
      "name": "Nguyen Van A",
      "email": "customer@example.com",
      "role": "customer"
    }
  }
}
```

Invalid credentials `401`:

```json
{
  "success": false,
  "message": "Invalid email or password."
}
```

Unverified email `403`:

```json
{
  "success": false,
  "message": "Email not verified. A new verification code has been sent to your email."
}
```

### 2.6 Get current user

```txt
GET /api/auth/me
```

Auth: Bearer token required

Success response:

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Nguyen Van A",
    "email": "customer@example.com",
    "role": "customer",
    "gender": "male",
    "birthday": "2000-01-01",
    "organizer_name": null,
    "tax_code": null,
    "email_verified_at": "2026-05-11T02:00:00.000000Z"
  }
}
```

### 2.7 Logout

```txt
POST /api/auth/logout
```

Auth: Bearer token required

Behavior:

- Revokes current Sanctum token.

Success response:

```json
{
  "success": true,
  "message": "Logged out successfully."
}
```

### 2.8 Update profile

```txt
PUT /api/auth/profile
```

Auth: Bearer token required.

Any authenticated user can update their own profile.

Body parameters (all optional):

| Name | Type | Description |
|---|---|---|
| `name` | string | Display name |

**Organizer-only fields:**

| Name | Type | Description |
|---|---|---|
| `organizer_name` | string | Tên tổ chức / công ty |
| `tax_code` | string | Mã số thuế |

Example request:

```json
{
  "organizer_name": "ABC Event Company",
  "tax_code": "1234567890"
}
```

Success response:

```json
{
  "success": true,
  "message": "Profile updated successfully.",
  "data": {
    "id": 2,
    "name": "Tran Van B",
    "email": "organizer.music@ticketrush.com",
    "role": "organizer",
    "organizer_name": "ABC Event Company",
    "tax_code": "1234567890"
  }
}
```

## 3. Demo seed data

Run all default demo seeders:

```powershell
php artisan db:seed
```

Or run only demo data:

```powershell
php artisan db:seed --class=DemoDataSeeder
```

All demo accounts use password:

```txt
password
```

### 3.1 Demo accounts

| Role | Email | Password | Notes |
|---|---|---|---|
| `admin` | `admin@ticketrush.com` | `password` | Default system admin. |
| `organizer` | `organizer.music@ticketrush.com` | `password` | Organizer for music, DJ, theater, conference events. |
| `organizer` | `organizer.sport@ticketrush.com` | `password` | Organizer for sport, workshop, comedy events. |
| `customer` | `customer@ticketrush.com` | `password` | Verified customer account. |
| `customer` | `customer2@ticketrush.com` | `password` | Verified customer account. |

### 3.2 Demo events

The demo seeder creates approved, pending, and rejected events for API testing.

| Event | Category | Status | Ticket Sale Status | Featured | Special |
|---|---|---|---|---|---|---|
| Neon Nights Festival 2024 | `dj` | `approved` | `on_sale` | Yes | Yes |
| Chung Kết Cúp Bóng Đá Vô Địch Quốc Gia | `sport` | `approved` | `on_sale` | Yes | Yes |
| The Midnight Sounds - Asia Tour 2024 | `music` | `approved` | `on_sale` | No | No |
| Tech Summit Vietnam: AI & Tương lai | `conference` | `approved` | `not_started` | No | No |
| Ravetopia 2024: Đêm Giao Thừa | `dj` | `approved` | `not_started` | No | No |
| Vở Nhạc Kịch: Tiếng Gọi Nơi Hoang Dã | `theater` | `approved` | `on_sale` | No | No |
| Workshop Sáng tạo Nội dung 2024 | `workshop` | `pending` | `not_started` | No | No |
| Comedy Night: Cười Xuyên Đêm | `comedy` | `rejected` | `ended` | No | No |

Each demo event includes:

- Two seating zones: `VIP`, `Standard`.
- One non-seating walkway zone: `Lối đi trung tâm`.
- Generated seats with sample statuses: `available`, `locked`, `sold`.

### 3.3 Admin account

```txt
email: admin@ticketrush.com
password: password
```

Admin-only seed command:

```powershell
php artisan db:seed --class=AdminSeeder
```

## 4. Role middleware test APIs

All endpoints require Bearer token.

### 4.1 Admin ping

```txt
GET /api/admin/ping
```

Role: `admin`

Success:

```json
{
  "success": true,
  "message": "Admin access granted."
}
```

### 4.2 Organizer ping

```txt
GET /api/organizer/ping
```

Role: `organizer`

Success:

```json
{
  "success": true,
  "message": "Organizer access granted."
}
```

### 4.3 Customer ping

```txt
GET /api/customer/ping
```

Role: `customer`

Success:

```json
{
  "success": true,
  "message": "Customer access granted."
}
```

Forbidden response `403`:

```json
{
  "success": false,
  "message": "Forbidden. You do not have permission to access this resource."
}
```

## 5. Event categories

Backend stores and returns only the category `key`.

Current multilingual policy:

- TicketRush does not support multilingual event content yet.
- Event fields such as `name`, `description`, and `venue` are currently single-language text.
- Category labels below are Vietnamese display suggestions for FE.
- If multilingual support is needed later, update this documentation and API design first.

### 5.1 Supported category keys

| Key | Vietnamese label suggestion | Suggested icon |
|---|---|---|
| `music` | Nhạc sống | `music` |
| `dj` | DJ / EDM | `disc` |
| `theater` | Sân khấu & Nghệ thuật | `theater` |
| `sport` | Thể thao | `trophy` |
| `workshop` | Hội thảo & Workshop | `users` |
| `conference` | Hội nghị | `presentation` |
| `comedy` | Hài kịch | `smile` |
| `family` | Gia đình | `heart` |
| `other` | Khác | `ticket` |

### 5.2 FE TypeScript const suggestion

```ts
export const EVENT_CATEGORY_KEYS = [
  'music',
  'dj',
  'theater',
  'sport',
  'workshop',
  'conference',
  'comedy',
  'family',
  'other',
] as const;

export type EventCategoryKey = (typeof EVENT_CATEGORY_KEYS)[number];

export const EVENT_CATEGORIES: Array<{
  key: EventCategoryKey;
  label: string;
  icon: string;
}> = [
  { key: 'music', label: 'Nhạc sống', icon: 'music' },
  { key: 'dj', label: 'DJ / EDM', icon: 'disc' },
  { key: 'theater', label: 'Sân khấu & Nghệ thuật', icon: 'theater' },
  { key: 'sport', label: 'Thể thao', icon: 'trophy' },
  { key: 'workshop', label: 'Hội thảo & Workshop', icon: 'users' },
  { key: 'conference', label: 'Hội nghị', icon: 'presentation' },
  { key: 'comedy', label: 'Hài kịch', icon: 'smile' },
  { key: 'family', label: 'Gia đình', icon: 'heart' },
  { key: 'other', label: 'Khác', icon: 'ticket' },
];
```

### 5.3 API category behavior

- Event create/update accepts only supported category keys.
- Public event APIs return the category key in `category`.
- FE should map `category` to a display label from its local constants.
- Do not expect category labels from backend event objects.

## 6. Public Event APIs

Public event APIs can be used by both anonymous users and logged-in customers.

Only events with `status = approved` are returned.

### 6.1 Categories

```txt
GET /api/categories
```

Auth: Public

Success response:

```json
{
  "success": true,
  "data": [
    { "key": "music", "name": "Nhạc sống", "icon": "music" },
    { "key": "dj", "name": "DJ / EDM", "icon": "disc" },
    { "key": "theater", "name": "Sân khấu & Nghệ thuật", "icon": "theater" },
    { "key": "sport", "name": "Thể thao", "icon": "trophy" },
    { "key": "workshop", "name": "Hội thảo & Workshop", "icon": "users" },
    { "key": "conference", "name": "Hội nghị", "icon": "presentation" },
    { "key": "comedy", "name": "Hài kịch", "icon": "smile" },
    { "key": "family", "name": "Gia đình", "icon": "heart" },
    { "key": "other", "name": "Khác", "icon": "ticket" }
  ]
}
```

FE usage: map `key` để filter, `name` để hiển thị label, `icon` để render icon.

### 6.2 Event list

```txt
GET /api/events
```

Auth: Public

Query parameters (all optional):

| Name | Required | Description |
|---|---:|---|
| `category` | No | Filter by category key |
| `q` | No | Search keyword across name, description, venue |
| `starts_after` | No | Event starts at or after this date (ISO 8601) |
| `starts_before` | No | Event starts at or before this date (ISO 8601) |
| `sale_starts_after` | No | Ticket sale starts at or after this date |
| `sale_starts_before` | No | Ticket sale starts at or before this date |
| `ticket_status` | No | `on_sale`, `sold_out`, `not_started`, `ended` |
| `is_featured` | No | `1` to filter featured events |
| `is_special` | No | `1` to filter special events |
| `trending` | No | `1` to sort by tickets sold in last 30 days |
| `limit` | No | Max number of results (returns array, no pagination) |
| `per_page` | No | Page size for pagination, default 12 |

Behavior:

- If `limit` is provided, returns a plain array without pagination `meta`.
- If `per_page` is used (or default), returns paginated response with `meta`.
- `trending=1` automatically filters `on_sale` events with available seats and sorts by `tickets_sold_count` descending.
- Default order (without `trending`): `is_featured` desc → `sort_order` → `starts_at`.

**Homepage section examples:**

Featured hero (limit 2):

```txt
GET /api/events?is_featured=1&limit=2
```

Special events (limit 8):

```txt
GET /api/events?is_special=1&limit=8
```

Trending events (limit 6):

```txt
GET /api/events?trending=1&limit=6
```

This week events — FE passes week bounds:

```txt
GET /api/events?starts_after=2024-11-11T00:00:00&starts_before=2024-11-17T23:59:59&limit=6
```

This month events — FE passes month bounds:

```txt
GET /api/events?starts_after=2024-11-01T00:00:00&starts_before=2024-11-30T23:59:59&limit=6
```

Upcoming sale events:

```txt
GET /api/events?sale_starts_after=2024-11-12T00:00:00&limit=6
```

Category filter:

```txt
GET /api/events?category=music&limit=12
```

Search + ticket status (paginated):

```txt
GET /api/events?q=festival&ticket_status=on_sale&per_page=12
```

Success response (with `limit` — no pagination):

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Neon Nights Festival 2024",
      "description": "Đêm nhạc điện tử bùng nổ với dàn line-up quốc tế.",
      "category": "dj",
      "thumbnail_url": "https://example.com/neon-thumb.jpg",
      "banner_url": "https://example.com/neon-banner.jpg",
      "venue": "Nhà thi đấu Phú Thọ",
      "starts_at": "2024-11-15T20:00:00+07:00",
      "ends_at": "2024-11-15T23:00:00+07:00",
      "ticket_sale_starts_at": "2024-11-01T10:00:00+07:00",
      "ticket_sale_ends_at": "2024-11-13T23:59:00+07:00",
      "is_sold_out": false,
      "ticket_sale_status": "on_sale",
      "display_type": "stadium",
      "is_featured": true,
      "organizer": {
        "id": 2,
        "name": "Tran Van B",
        "organizer_name": "ABC Event Company"
      }
    }
  ]
}
```

Success response (paginated — default or `per_page`):

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Neon Nights Festival 2024",
      "category": "dj",
      "thumbnail_url": "https://example.com/neon-thumb.jpg",
      "venue": "Nhà thi đấu Phú Thọ",
      "starts_at": "2024-11-15T20:00:00+07:00",
      "ends_at": "2024-11-15T23:00:00+07:00",
      "ticket_sale_starts_at": "2024-11-01T10:00:00+07:00",
      "ticket_sale_ends_at": "2024-11-13T23:59:00+07:00",
      "is_sold_out": false,
      "ticket_sale_status": "on_sale",
      "display_type": "stadium",
      "is_featured": true,
      "organizer": {
        "id": 2,
        "name": "Tran Van B",
        "organizer_name": "ABC Event Company"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 12,
    "total": 1
  }
}
```

### 6.3 Event detail

```txt
GET /api/events/{event}
```

Auth: Public

Behavior:

- Returns event only if `status = approved`.
- Returns `404` if event is not approved.

Success response:

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Neon Nights Festival 2024",
    "description": "Đêm nhạc điện tử bùng nổ với dàn line-up quốc tế.",
    "category": "dj",
    "thumbnail_url": "https://example.com/neon-thumb.jpg",
    "banner_url": "https://example.com/neon-banner.jpg",
    "venue": "Nhà thi đấu Phú Thọ",
    "starts_at": "2024-11-15T20:00:00+07:00",
    "ends_at": "2024-11-15T23:00:00+07:00",
    "ticket_sale_starts_at": "2024-11-01T10:00:00+07:00",
    "ticket_sale_ends_at": "2024-11-13T23:59:00+07:00",
    "is_sold_out": false,
    "ticket_sale_status": "on_sale",
    "display_type": "stadium",
    "is_featured": true,
    "organizer": {
      "id": 2,
      "name": "Tran Van B",
      "organizer_name": "ABC Event Company"
    }
  }
}
```

## 7. Organizer Event APIs

Organizer event APIs require Bearer token and role `organizer`.

### 7.1 List organizer events

```txt
GET /api/organizer/events
```

Query parameters:

| Name | Required | Description |
|---|---:|---|
| `status` | No | Filter by `pending`, `approved`, `rejected` |
| `category` | No | Filter by category key |
| `starts_after` | No | Event starts at or after this date |
| `starts_before` | No | Event starts at or before this date |
| `per_page` | No | Page size, default 12 |

Example:

```txt
GET /api/organizer/events?status=pending&category=dj&per_page=12
```

Response:

```json
{
  "data": [
    {
      "id": 1,
      "organizer_id": 2,
      "name": "Neon Nights Festival 2024",
      "description": "Đêm nhạc điện tử bùng nổ với dàn line-up quốc tế.",
      "category": "dj",
      "thumbnail_url": "https://example.com/neon-thumb.jpg",
      "banner_url": "https://example.com/neon-banner.jpg",
      "is_featured": true,
      "is_special": false,
      "sort_order": 1,
      "venue": "Nhà thi đấu Phú Thọ",
      "starts_at": "2024-11-15T20:00:00+07:00",
      "ends_at": "2024-11-15T23:00:00+07:00",
      "ticket_sale_starts_at": "2024-11-01T10:00:00+07:00",
      "ticket_sale_ends_at": "2024-11-13T23:59:00+07:00",
      "is_sold_out": false,
      "ticket_sale_status": "on_sale",
      "status": "pending",
      "display_type": "stadium",
      "master_width": 80,
      "master_length": 60
    }
  ]
}
```

### 7.2 Create organizer event

```txt
POST /api/organizer/events
```

Request body:

```json
{
  "name": "Neon Nights Festival 2024",
  "description": "Đêm nhạc điện tử bùng nổ với dàn line-up quốc tế.",
  "category": "dj",
  "thumbnail_url": "https://example.com/neon-thumb.jpg",
  "banner_url": "https://example.com/neon-banner.jpg",
  "is_featured": true,
  "sort_order": 1,
  "venue": "Nhà thi đấu Phú Thọ",
  "starts_at": "2024-11-15 20:00:00",
  "ends_at": "2024-11-15 23:00:00",
  "ticket_sale_starts_at": "2024-11-01 10:00:00",
  "ticket_sale_ends_at": "2024-11-13 23:59:00",
  "display_type": "stadium",
  "master_width": 80,
  "master_length": 60,
  "bank_name": "Vietcombank",
  "bank_account_number": "1234567890",
  "bank_account_name": "NGUYEN VAN A"
}
```

Validation:

| Field | Required | Rule |
|---|---:|---|
| `name` | Yes | string, max 255 |
| `description` | No | string |
| `category` | Yes | one of supported category keys |
| `thumbnail_url` | No | string, max 2048 |
| `banner_url` | No | string, max 2048 |
| `is_featured` | No | boolean |
| `is_special` | No | boolean |
| `sort_order` | No | integer, min 0 |
| `venue` | No | string, max 255 |
| `starts_at` | No | date |
| `ends_at` | No | date, after_or_equal starts_at |
| `ticket_sale_starts_at` | No | date |
| `ticket_sale_ends_at` | No | date, after_or_equal ticket_sale_starts_at |
| `bank_name` | No | string, max 255 |
| `bank_account_number` | No | string, max 50 |
| `bank_account_name` | No | string, max 255 |
| `display_type` | Yes | `rectangular`, `stadium` |
| `master_width` | Yes | integer, min 1, max 1000 |
| `master_length` | Yes | integer, min 1, max 1000 |

Behavior:

- `organizer_id` is taken from authenticated user.
- `status` is automatically set to `pending`.
- Admin approval is required before the event appears publicly.

Success response `201`:

```json
{
  "success": true,
  "message": "Event created successfully and is waiting for admin approval.",
  "data": {
    "id": 1,
    "organizer_id": 2,
    "name": "Neon Nights Festival 2024",
    "description": "Đêm nhạc điện tử bùng nổ với dàn line-up quốc tế.",
    "category": "dj",
    "thumbnail_url": "https://example.com/neon-thumb.jpg",
    "banner_url": "https://example.com/neon-banner.jpg",
    "is_featured": true,
    "is_special": false,
    "sort_order": 1,
    "venue": "Nhà thi đấu Phú Thọ",
    "starts_at": "2024-11-15T20:00:00+07:00",
    "ends_at": "2024-11-15T23:00:00+07:00",
    "ticket_sale_starts_at": null,
    "ticket_sale_ends_at": null,
    "bank_name": "Vietcombank",
    "bank_account_number": "1234567890",
    "bank_account_name": "NGUYEN VAN A",
    "is_sold_out": false,
    "ticket_sale_status": "on_sale",
    "status": "pending",
    "display_type": "stadium",
    "master_width": 80,
    "master_length": 60
  }
}
```

### 7.3 Show organizer event

```txt
GET /api/organizer/events/{event}
```

Behavior:

- Organizer can only view events owned by themselves.
- Returns `403` if accessing another organizer's event.

### 7.4 Update organizer event

```txt
PUT /api/organizer/events/{event}
PATCH /api/organizer/events/{event}
```

Request body can be partial:

```json
{
  "name": "Neon Nights Festival 2024 - Updated",
  "category": "music",
  "is_featured": true,
  "sort_order": 2,
  "bank_name": "Vietcombank",
  "bank_account_number": "1234567890",
  "bank_account_name": "NGUYEN VAN A"
}
```

Behavior:

- Organizer can only update events owned by themselves.
- After update, `status` is reset to `pending` for admin review again.

### 7.5 Delete organizer event

```txt
DELETE /api/organizer/events/{event}
```

Behavior:

- Organizer can only delete events owned by themselves.

Success response:

```json
{
  "success": true,
  "message": "Event deleted successfully."
}
```

### 7.6 Organizer Zone APIs

Organizer zone APIs require Bearer token and role `organizer`.

Each zone belongs to an event. When a seating zone (`is_seating = true`) is created, seats are automatically generated in a grid of `width × length`.

#### 7.6.1 List zones

```txt
GET /api/organizer/events/{event}/zones
```

Success response:

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "event_id": 1,
      "name": "VIP",
      "price": 1500000,
      "color": "#FF4444",
      "icon_url": null,
      "pos_x": 2,
      "pos_y": 2,
      "width": 10,
      "length": 5,
      "is_seating": true,
      "seats_count": 50,
      "created_at": "2026-05-11T08:00:00.000000Z",
      "updated_at": "2026-05-11T08:00:00.000000Z"
    }
  ]
}
```

#### 7.6.2 Create zone

```txt
POST /api/organizer/events/{event}/zones
```

Body parameters:

| Name | Required | Type | Description |
|---|---|---|---|
| `name` | Yes | string | Zone display name |
| `price` | Yes | numeric | Ticket price for this zone |
| `color` | Yes | string | Display color (hex) |
| `icon_url` | No | string | Optional icon URL |
| `pos_x` | Yes | integer | Horizontal position on master grid |
| `pos_y` | Yes | integer | Vertical position on master grid |
| `width` | Yes | integer | Grid slots width (1–1000) |
| `length` | Yes | integer | Grid slots length (1–1000) |
| `is_seating` | No | boolean | Default `true`. If `false`, no seats generated. |

Example request:

```json
{
  "name": "VIP",
  "price": 1500000,
  "color": "#FF4444",
  "pos_x": 2,
  "pos_y": 2,
  "width": 10,
  "length": 5,
  "is_seating": true
}
```

Success response:

```json
{
  "success": true,
  "message": "Zone created successfully.",
  "data": {
    "id": 1,
    "event_id": 1,
    "name": "VIP",
    "price": 1500000,
    "color": "#FF4444",
    "icon_url": null,
    "pos_x": 2,
    "pos_y": 2,
    "width": 10,
    "length": 5,
    "is_seating": true,
    "seats_count": 50
  }
}
```

#### 7.6.3 Show zone

```txt
GET /api/organizer/events/{event}/zones/{zone}
```

Includes full seat grid:

```json
{
  "success": true,
  "data": {
    "id": 1,
    "event_id": 1,
    "name": "VIP",
    "price": 1500000,
    "color": "#FF4444",
    "icon_url": null,
    "pos_x": 2,
    "pos_y": 2,
    "width": 10,
    "length": 5,
    "is_seating": true,
    "seats_count": 50,
    "seats": [
      {
        "id": 1,
        "row_index": 0,
        "col_index": 0,
        "status": "available"
      }
    ],
    "created_at": "2026-05-11T08:00:00.000000Z",
    "updated_at": "2026-05-11T08:00:00.000000Z"
  }
}
```

#### 7.6.4 Update zone

```txt
PUT /api/organizer/events/{event}/zones/{zone}
```

Body parameters: same as create, all optional (`sometimes` required).

Success response:

```json
{
  "success": true,
  "message": "Zone updated successfully.",
  "data": { ... }
}
```

#### 7.6.5 Delete zone

```txt
DELETE /api/organizer/events/{event}/zones/{zone}
```

Success response:

```json
{
  "success": true,
  "message": "Zone deleted successfully."
}
```

## 8. Admin Event APIs

Admin event APIs require Bearer token and role `admin`.

Admin can update any event field, including `status` (approve/reject) and `is_special` (mark as special).

### 8.1 Update event (approve / reject / mark special)

```txt
PUT /api/admin/events/{event}
```

Request body is partial — only send fields you want to change:

**Approve event:**

```json
{
  "status": "approved"
}
```

**Reject event:**

```json
{
  "status": "rejected"
}
```

**Mark event as special:**

```json
{
  "is_special": true
}
```

**Mark as featured and special at the same time:**

```json
{
  "is_featured": true,
  "is_special": true,
  "status": "approved"
}
```

**Update ticket sale window:**

```json
{
  "ticket_sale_starts_at": "2024-11-01 10:00:00",
  "ticket_sale_ends_at": "2024-11-13 23:59:00"
}
```

Validation:

| Field | Required | Rule |
|---|---:|---|
| `name` | No | string, max 255 |
| `description` | No | string |
| `category` | No | one of supported category keys |
| `thumbnail_url` | No | string, max 2048 |
| `banner_url` | No | string, max 2048 |
| `is_featured` | No | boolean |
| `is_special` | No | boolean |
| `sort_order` | No | integer, min 0 |
| `venue` | No | string, max 255 |
| `starts_at` | No | date |
| `ends_at` | No | date, after_or_equal starts_at |
| `ticket_sale_starts_at` | No | date |
| `ticket_sale_ends_at` | No | date, after_or_equal ticket_sale_starts_at |
| `status` | No | `pending`, `approved`, `rejected` |
| `display_type` | No | `rectangular`, `stadium` |
| `master_width` | No | integer, min 1, max 1000 |
| `master_length` | No | integer, min 1, max 1000 |

Behavior:

- Admin can update any event, regardless of organizer.
- Only sent fields are updated; omitted fields remain unchanged.
- `status` can be changed to `approved` or `rejected` directly.

Success response:

```json
{
  "success": true,
  "message": "Event updated successfully.",
  "data": {
    "id": 1,
    "organizer_id": 2,
    "name": "Neon Nights Festival 2024",
    "status": "approved",
    "is_special": true,
    "is_featured": true,
    "category": "dj"
  }
}
```

## 9. Customer Order & Ticket APIs

Auth: Bearer token required. Role: `customer`.

These APIs allow customers to view their own paid orders and issued tickets.

### 9.1 List customer orders

```txt
GET /api/customer/orders
```

Query parameters:

| Name | Required | Description |
|---|---:|---|
| `per_page` | No | Page size, default 12 |

Success response:

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "order_code": "ORD-202411150001",
      "event": {
        "id": 1,
        "name": "Neon Nights Festival 2024",
        "thumbnail_url": "https://images.unsplash.com/photo-1501386761578-eac5c94b800a",
        "starts_at": "2024-11-15T20:00:00+07:00",
        "venue": "Nhà thi đấu Phú Thọ, TP.HCM"
      },
      "subtotal_amount": "3000000.00",
      "total_amount": "3150000.00",
      "currency": "VND",
      "status": "paid",
      "payment_method": "mock",
      "paid_at": "2024-11-10T10:30:00+07:00",
      "expires_at": null,
      "ticket_count": 2,
      "created_at": "2024-11-10T10:25:00+07:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 12,
    "total": 1
  }
}
```

### 9.2 Show customer order

```txt
GET /api/customer/orders/{order}
```

Behavior:

- Customer can only view orders placed by themselves.
- Returns `403` if accessing another customer's order.

Success response:

```json
{
  "success": true,
  "data": {
    "id": 1,
    "order_code": "ORD-202411150001",
    "event": {
      "id": 1,
      "name": "Neon Nights Festival 2024",
      "thumbnail_url": "https://images.unsplash.com/photo-1501386761578-eac5c94b800a",
      "starts_at": "2024-11-15T20:00:00+07:00",
      "venue": "Nhà thi đấu Phú Thọ, TP.HCM"
    },
    "subtotal_amount": "3000000.00",
    "total_amount": "3150000.00",
    "currency": "VND",
    "status": "paid",
    "payment_method": "mock",
    "payment_reference": "MOCK-REF-123456",
    "paid_at": "2024-11-10T10:30:00+07:00",
    "expires_at": null,
    "created_at": "2024-11-10T10:25:00+07:00",
    "tickets": [
      {
        "id": 1,
        "ticket_code": "TICK-202411150001",
        "qr_code": "QR-202411150001",
        "status": "valid",
        "issued_at": "2024-11-10T10:30:00+07:00",
        "checked_in_at": null
      }
    ]
  }
}
```

### 9.3 List customer tickets

```txt
GET /api/customer/tickets
```

Query parameters:

| Name | Required | Description |
|---|---:|---|
| `per_page` | No | Page size, default 12 |

Success response:

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "ticket_code": "TICK-202411150001",
      "qr_code": "QR-202411150001",
      "status": "valid",
      "issued_at": "2024-11-10T10:30:00+07:00",
      "checked_in_at": null,
      "event": {
        "id": 1,
        "name": "Neon Nights Festival 2024",
        "thumbnail_url": "https://images.unsplash.com/photo-1501386761578-eac5c94b800a",
        "starts_at": "2024-11-15T20:00:00+07:00",
        "venue": "Nhà thi đấu Phú Thọ, TP.HCM"
      },
      "seat": {
        "id": 1,
        "row_index": 1,
        "col_index": 1,
        "zone": {
          "id": 1,
          "name": "VIP",
          "price": "1500000.00"
        }
      },
      "order": {
        "id": 1,
        "order_code": "ORD-202411150001",
        "total_amount": "3150000.00"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 12,
    "total": 1
  }
}
```

### 9.4 Show customer ticket

```txt
GET /api/customer/tickets/{ticket}
```

Behavior:

- Customer can only view tickets issued to themselves.
- Returns `403` if accessing another customer's ticket.

Success response:

```json
{
  "success": true,
  "data": {
    "id": 1,
    "ticket_code": "TICK-202411150001",
    "qr_code": "QR-202411150001",
    "status": "valid",
    "issued_at": "2024-11-10T10:30:00+07:00",
    "checked_in_at": null,
    "event": {
      "id": 1,
      "name": "Neon Nights Festival 2024",
      "thumbnail_url": "https://images.unsplash.com/photo-1501386761578-eac5c94b800a",
      "starts_at": "2024-11-15T20:00:00+07:00",
      "venue": "Nhà thi đấu Phú Thọ, TP.HCM"
    },
    "seat": {
      "id": 1,
      "row_index": 1,
      "col_index": 1,
      "zone": {
        "id": 1,
        "name": "VIP",
        "price": "1500000.00"
      }
    },
    "order": {
      "id": 1,
      "order_code": "ORD-202411150001",
      "total_amount": "3150000.00"
    },
    "created_at": "2024-11-10T10:30:00+07:00"
  }
}
```

## 9. Current migration requirements

Before manual testing, run:

```powershell
php artisan migrate
php artisan db:seed --class=AdminSeeder
```

## 10. Mail configuration

For real email delivery, configure SMTP in `.env`.

For local development, use log mailer:

```env
MAIL_MAILER=log
```

Then read verification codes in:

```txt
storage/logs/laravel.log
```

## 10. Documentation update rule

This file is the API source of truth for frontend integration.

Whenever backend changes affect API behavior, update this file in the same task, including:

- New routes.
- Removed routes.
- Changed request body.
- Changed response fields.
- Changed validation rules.
- Changed auth or role requirements.
- Changed status code or error message behavior.
- Changed enum/category/status values.
- Changed pagination or query parameters.
