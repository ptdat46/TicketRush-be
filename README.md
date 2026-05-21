# TicketRush Backend

## Setup local

### Yêu cầu

- PHP `>= 8.2`
- Composer
- Node.js và npm
- MySQL đang chạy local

> Nếu Windows báo `php : The term 'php' is not recognized`, hãy đóng terminal hiện tại rồi mở terminal mới để PATH được cập nhật. Kiểm tra lại bằng `php -v`.

### Cài đặt

1. Cài thư viện PHP:

```bash
composer install
```

2. Cài thư viện frontend:

```bash
npm install
```

3. Tạo file môi trường:

```bash
cp .env.example .env
```

Trên Windows PowerShell có thể dùng:

```powershell
Copy-Item .env.example .env
```

4. Cấu hình MySQL trong `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ticketrush
DB_USERNAME=root
DB_PASSWORD=
```

Nếu MySQL local có mật khẩu, điền vào `DB_PASSWORD`.

5. Tạo database MySQL:

```sql
CREATE DATABASE ticketrush CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Có thể chạy bằng terminal:

```bash
mysql -uroot -p -e "CREATE DATABASE IF NOT EXISTS ticketrush CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

6. Tạo app key:

```bash
php artisan key:generate
```

7. Chạy migration và seed dữ liệu mẫu:

```bash
php artisan migrate --seed
```

8. Chạy backend local:

```bash
php artisan serve
```

Backend mặc định chạy tại:

```text
http://127.0.0.1:8000
```

Endpoint test nhanh:

```text
GET http://127.0.0.1:8000/api/events
```

Nếu cổng `8000` đã được dùng:

```bash
php artisan serve --port=8001
```

### Build frontend assets

Nếu cần build assets:

```bash
npm run build
```

## Tài khoản seeder

Tất cả tài khoản seed mặc định đều dùng mật khẩu: `password`.

| Role | Email |
| --- | --- |
| Admin | `admin@ticketrush.com` |
| Organizer | `organizer.music@ticketrush.com` |
| Organizer | `organizer.sport@ticketrush.com` |
| Customer | `customer@ticketrush.com` |
| Customer | `customer2@ticketrush.com` |

## Admin Event API

Các API bên dưới yêu cầu đăng nhập bằng Sanctum token và user có role `admin`.

| Method | Endpoint | Mục đích |
| --- | --- | --- |
| `GET` | `/api/admin/events` | Danh sách event cho admin |
| `GET` | `/api/admin/events/pending` | Danh sách event đang chờ duyệt |
| `GET` | `/api/admin/events/{event}` | Xem chi tiết event |
| `PUT` | `/api/admin/events/{event}` | Sửa thông tin event được phép |
| `PATCH` | `/api/admin/events/{event}/review` | Duyệt hoặc từ chối pending event |
| `PATCH` | `/api/admin/events/{event}/homepage` | Cập nhật hiển thị trang chính |

### Lọc danh sách event

`GET /api/admin/events` hỗ trợ các query params:

- `status`: `pending`, `approved`, `rejected`
- `category`: category của event
- `is_featured`: `true` hoặc `false`
- `is_special`: `true` hoặc `false`
- `search`: tìm theo tên event hoặc venue
- `per_page`: số item mỗi trang, tối đa `100`

### Duyệt pending event

```http
PATCH /api/admin/events/{event}/review
Content-Type: application/json

{
  "status": "approved"
}
```

`status` chỉ nhận `approved` hoặc `rejected`. Endpoint này chỉ xử lý event đang ở trạng thái `pending`.

### Sửa thông tin event

`PUT /api/admin/events/{event}` cho phép admin sửa các thông tin như tên, mô tả, category, ảnh, venue, thời gian diễn ra, thời gian bán vé, status và thứ tự hiển thị.

Admin không được sửa các nhóm thông tin sau qua endpoint này:

- Thông tin ngân hàng: `bank_name`, `bank_account_number`, `bank_account_name`
- Bản đồ event: `display_type`, `master_width`, `master_length`
- Zones, số zones, giá zones

### Chọn event lên trang chính

```http
PATCH /api/admin/events/{event}/homepage
Content-Type: application/json

{
  "is_featured": true,
  "is_special": true,
  "sort_order": 5
}
```

Các field đều optional, nhưng nếu gửi lên thì phải đúng kiểu dữ liệu.

## Customer Booking API

Các API bên dưới yêu cầu đăng nhập bằng Sanctum token và user có role `customer`.

| Method | Endpoint | Mục đích |
| --- | --- | --- |
| `POST` | `/api/customer/events/{event}/seats/lock` | Lock các ghế customer vừa chọn trong 10 phút |
| `DELETE` | `/api/customer/events/{event}/seats/lock` | Nhả các ghế customer đã lock |
| `POST` | `/api/customer/events/{event}/orders` | Checkout các ghế đã lock và tạo vé |
| `GET` | `/api/customer/orders` | Xem danh sách order của tôi |
| `GET` | `/api/customer/tickets` | Xem danh sách vé của tôi |

`GET /api/customer/tickets` hỗ trợ filter `status=valid|used|expired|void` và sort bằng `sort_by=issued_at|event_starts_at|created_at|status`, `sort_direction=asc|desc`.
