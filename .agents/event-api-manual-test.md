# TicketRush Event API - Manual Test Guide

## 1. Chuẩn bị dữ liệu

```powershell
php artisan migrate
php artisan db:seed
```

Tài khoản seed mặc định đều dùng mật khẩu `password`.

- Admin: `admin@ticketrush.com`
- Organizer: `organizer.music@ticketrush.com`
- Customer: `customer@ticketrush.com`

## 2. Categories đang hỗ trợ

- `music`: Nhạc sống
- `dj`: DJ / EDM
- `theater`: Sân khấu & Nghệ thuật
- `sport`: Thể thao
- `workshop`: Hội thảo & Workshop
- `conference`: Hội nghị
- `comedy`: Hài kịch
- `family`: Gia đình
- `other`: Khác

## 3. Organizer tạo event

`POST /api/organizer/events`

Headers:

```txt
Authorization: Bearer <organizer_token>
Accept: application/json
Content-Type: application/json
```

Body:

```json
{
  "name": "Neon Nights Festival 2024",
  "description": "Đêm nhạc điện tử với dàn line-up quốc tế.",
  "category": "dj",
  "thumbnail_url": "https://example.com/neon-thumb.jpg",
  "banner_url": "https://example.com/neon-banner.jpg",
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

Event mới tạo có `status = pending`. Chỉ event `approved` mới hiển thị ở public APIs.

## 4. Organizer xem và cập nhật event

List event của organizer:

```txt
GET /api/organizer/events?status=pending&category=dj&per_page=12
```

Chi tiết event:

```txt
GET /api/organizer/events/{event}
```

Cập nhật event:

```txt
PUT /api/organizer/events/{event}
```

Body có thể gửi một phần field:

```json
{
  "name": "Neon Nights Festival 2024 - Updated",
  "category": "music",
  "venue": "SECC"
}
```

Sau khi organizer cập nhật, event bị đưa lại về `pending` để admin duyệt lại.

## 5. Admin xem pending events

`GET /api/admin/events/pending`

Headers:

```txt
Authorization: Bearer <admin_token>
Accept: application/json
```

Expected:

- Status `200`.
- Response `data` chỉ gồm events có `status = pending`.
- Mỗi item có thể có `organizer`, `zones_count`, `seats_count`, `available_seats_count`.

## 6. Admin list tất cả events

`GET /api/admin/events`

Query optional:

```txt
?status=approved&category=dj&is_featured=true&is_special=false&search=festival&per_page=12
```

Các filter:

- `status`: `pending`, `approved`, `rejected`
- `category`: category key
- `is_featured`: boolean
- `is_special`: boolean
- `search`: tìm theo `name` hoặc `venue`
- `per_page`: 1-100, mặc định 12

## 7. Admin duyệt hoặc từ chối pending event

`PATCH /api/admin/events/{event}/review`

Duyệt:

```json
{
  "status": "approved"
}
```

Từ chối:

```json
{
  "status": "rejected"
}
```

Lưu ý:

- Chỉ nhận `approved` hoặc `rejected`.
- Endpoint này chỉ xử lý event đang `pending`.
- Nếu event không phải `pending`, response `422` với message `Only pending events can be reviewed from this endpoint.`

## 8. Admin sửa thông tin event được phép

`PUT /api/admin/events/{event}`

Ví dụ:

```json
{
  "name": "Neon Nights Festival 2024 - Official",
  "description": "Thông tin đã được admin chỉnh sửa.",
  "venue": "SECC",
  "status": "approved",
  "ticket_sale_starts_at": "2024-11-01 10:00:00",
  "ticket_sale_ends_at": "2024-11-13 23:59:00"
}
```

Admin không được sửa các nhóm field sau qua endpoint này:

- Bank fields: `bank_name`, `bank_account_number`, `bank_account_name`
- Map fields: `display_type`, `master_width`, `master_length`
- Zones, số zones, giá zones: `zones`, `zone_count`, `zones_count`, `zone_prices`

Manual negative test:

```json
{
  "bank_name": "Changed Bank",
  "display_type": "rectangular",
  "master_width": 100
}
```

Expected: response `422` có validation errors cho các field bị cấm.

## 9. Admin chọn event lên trang chính

`PATCH /api/admin/events/{event}/homepage`

```json
{
  "is_featured": true,
  "is_special": true,
  "sort_order": 5
}
```

Các field đều optional, nhưng nếu gửi lên thì phải đúng kiểu dữ liệu:

- `is_featured`: boolean
- `is_special`: boolean
- `sort_order`: integer, min 0

## 10. Public event listing - không cần đăng nhập

`GET /api/events`

Query optional:

```txt
?category=music&q=festival&ticket_status=on_sale&per_page=12
```

Homepage-style examples:

```txt
GET /api/events?is_featured=1&limit=2
GET /api/events?is_special=1&limit=8
GET /api/events?trending=1&limit=6
```

Chỉ trả về event có `status = approved`.

## 11. Public event detail - không cần đăng nhập

`GET /api/events/{event}`

Chỉ trả về event có `status = approved`. Event chưa duyệt hoặc bị từ chối trả về `404`.

## 12. Customer lock ghế

`POST /api/customer/events/{event}/seats/lock`

Headers:

```txt
Authorization: Bearer <customer_token>
Accept: application/json
Content-Type: application/json
```

Body:

```json
{
  "seat_ids": [1, 2]
}
```

Expected:

- Status `200`.
- Seats chuyển sang `status = locked`.
- `locked_by` là customer hiện tại.
- Response có `locked_until`, lock kéo dài 10 phút.

Nếu ghế đang bị customer khác lock và chưa hết hạn, expected status `409`.

## 13. Customer nhả ghế đã lock

`DELETE /api/customer/events/{event}/seats/lock`

Body:

```json
{
  "seat_ids": [1, 2]
}
```

Expected:

- Status `200`.
- Chỉ các ghế do chính customer hiện tại lock được nhả.
- Ghế được nhả chuyển về `available`.

## 14. Customer checkout ghế đã lock

`POST /api/customer/events/{event}/orders`

Body:

```json
{
  "seat_ids": [1, 2],
  "payment_method": "mock",
  "payment_reference": "MOCK-FE-123"
}
```

Expected:

- Status `201`.
- Tạo order `paid`.
- Tạo ticket cho từng ghế.
- Ghế chuyển sang `sold`.

Negative test:

- Checkout ghế chưa lock hoặc lock bởi customer khác trả về `409`.
- Checkout event chưa approved hoặc ngoài sale window trả về `422`.

## 15. Customer xem vé của tôi

`GET /api/customer/tickets`

Query optional:

```txt
?status=valid&sort_by=event_starts_at&sort_direction=asc&per_page=12
```

Filter status:

- `valid`: vé còn hiệu lực, event chưa kết thúc.
- `used`: vé đã dùng.
- `expired`: vé chưa dùng nhưng event đã kết thúc.
- `void`: vé bị hủy hiệu lực.

Sort:

- `sort_by=issued_at`
- `sort_by=event_starts_at`
- `sort_by=created_at`
- `sort_by=status`
- `sort_direction=asc|desc`
