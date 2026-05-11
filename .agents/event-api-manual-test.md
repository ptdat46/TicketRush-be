# TicketRush Event API - Manual Test Guide

## 1. Chạy migration mới

```powershell
php artisan migrate
```

Migration mới thêm vào bảng `events`:

- `category`
- `thumbnail_url`
- `banner_url`
- `is_featured`
- `sort_order`

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
  "description": "Đêm nhạc điện tử bùng nổ với dàn line-up quốc tế.",
  "category": "dj",
  "thumbnail_url": "https://example.com/neon-thumb.jpg",
  "banner_url": "https://example.com/neon-banner.jpg",
  "is_featured": true,
  "sort_order": 1,
  "venue": "Nhà thi đấu Phú Thọ",
  "starts_at": "2024-11-15 20:00:00",
  "ends_at": "2024-11-15 23:00:00",
  "display_type": "stadium",
  "master_width": 80,
  "master_length": 60
}
```

Event mới tạo có `status = pending`. Chỉ event `approved` mới hiện ở public homepage.

## 4. Organizer xem danh sách event của mình

`GET /api/organizer/events`

Query optional:

```txt
?status=pending&category=dj&per_page=12
```

## 5. Organizer xem chi tiết event

`GET /api/organizer/events/{id}`

## 6. Organizer cập nhật event

`PUT /api/organizer/events/{id}`

Body có thể gửi một phần field:

```json
{
  "name": "Neon Nights Festival 2024 - Updated",
  "category": "music",
  "is_featured": true,
  "sort_order": 2
}
```

Sau khi cập nhật, event bị đưa lại về `pending` để admin duyệt lại.

## 7. Organizer xóa event

`DELETE /api/organizer/events/{id}`

## 8. Public homepage - không cần đăng nhập

`GET /api/events/homepage`

Query optional:

```txt
?category=music
```

Response trả về:

- `categories`: danh sách tab category cho navbar giống design.
- `featured_events`: 2 event nổi bật cho banner/card lớn.
- `special_events`: danh sách event đặc biệt cho section bên dưới.

## 9. Public event listing - không cần đăng nhập

`GET /api/events`

Query optional:

```txt
?category=music&q=festival&per_page=12
```

## 10. Public event detail - không cần đăng nhập

`GET /api/events/{id}`

Chỉ trả về event có `status = approved`.

## 11. Lưu ý admin duyệt event

Hiện tại CRUD admin duyệt event chưa được viết ở bước này. Để test public homepage, bạn có thể tạm chỉnh DB cho event:

```sql
UPDATE events SET status = 'approved' WHERE id = 1;
```

Hoặc yêu cầu viết tiếp API admin approve/reject event.
