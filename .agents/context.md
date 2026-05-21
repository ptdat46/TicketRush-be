# TicketRush - Hệ thống đặt vé sự kiện trực tuyến

## 1. Tổng quan dự án

TicketRush là nền tảng phân phối vé điện tử cho sự kiện, tập trung vào:

- **Trải nghiệm chọn ghế trực quan**: Người dùng xem sơ đồ ghế theo từng khu vực và chọn ghế trực tiếp trên map.
- **Xử lý tranh chấp dữ liệu cao**: Đảm bảo một ghế không bị nhiều người mua cùng lúc trong các đợt mở bán cao điểm.
- **Quản trị sự kiện linh hoạt**: Organizer có thể tạo sự kiện, thiết kế sơ đồ, quản lý vé và xem thống kê.
- **Vé điện tử QR Code**: Sau khi thanh toán thành công, hệ thống phát hành vé QR cho khách hàng.

Mô hình sản phẩm tham chiếu giống các nền tảng đặt vé sự kiện như Ticketbox: tìm kiếm sự kiện, xem thông tin, chọn vé/chỗ ngồi, giữ chỗ, thanh toán và nhận vé điện tử.

## 2. Vai trò người dùng

### Admin

- Duyệt hoặc từ chối sự kiện do Organizer gửi lên.
- Xem danh sách tất cả sự kiện và lọc riêng các sự kiện `pending`.
- Sửa các thông tin public/marketing của event khi cần.
- Chọn event hiển thị ở trang chính bằng `is_featured`, `is_special` và `sort_order`.
- Không được sửa thông tin ngân hàng, cấu hình bản đồ, số zones hoặc giá zones của event.
- Quản lý người dùng, sự kiện, danh mục và dữ liệu toàn hệ thống.
- Theo dõi giao dịch, log hệ thống và các hành vi bất thường.

### Organizer

- Đăng ký và quản lý sự kiện.
- Thiết kế sơ đồ map theo mô hình Master Grid và Zones.
- Quản lý vùng vé, giá vé, ghế ngồi và trạng thái mở bán.
- Xem báo cáo doanh thu, lượng vé bán ra và thống kê khách hàng.

### Customer

- Tìm kiếm và xem chi tiết sự kiện.
- Chọn ghế hoặc khu vực vé trên sơ đồ trực quan.
- Giữ chỗ trong 10 phút.
- Thanh toán giả lập và nhận vé QR Code.
- Quản lý vé đã mua trong tài khoản cá nhân.

## 3. Logic thiết kế sơ đồ và ghế ngồi

Hệ thống áp dụng mô hình **Master Grid - Child Zones**.

### Master Map

Mỗi sự kiện có một bản đồ tổng thể với các thông tin:

- **display_type**: Kiểu hiển thị của sơ đồ.
  - `rectangular`: Sơ đồ chữ nhật.
  - `stadium`: Sơ đồ sân vận động.
- **master_width**: Chiều rộng tổng theo đơn vị ô lưới.
- **master_length**: Chiều dài tổng theo đơn vị ô lưới.

### Zones

Zone là các vùng con nằm trong Master Map.

- **event_id**: Sự kiện sở hữu zone.
- **name**: Tên vùng, ví dụ VIP, Standard, A1, B2.
- **price**: Giá vé áp dụng cho ghế trong zone.
- **color**: Màu hiển thị trên sơ đồ.
- **icon_url**: Icon đại diện nếu cần.
- **pos_x, pos_y**: Tọa độ của zone trong Master Grid.
- **width, length**: Kích thước zone theo số ô lưới.
- **is_seating**:
  - `true`: Khu vực có ghế, hệ thống tự sinh seats.
  - `false`: Lối đi, hành lang, sân cỏ hoặc vùng không bán ghế.

### Seats

Ghế được tự động sinh dựa trên kích thước của từng zone có `is_seating = true`.

- **zone_id**: Zone chứa ghế.
- **row_index**: Vị trí hàng trong zone.
- **col_index**: Vị trí cột trong zone.
- **status**:
  - `available`: Ghế còn trống.
  - `locked`: Ghế đang được giữ tạm thời.
  - `sold`: Ghế đã bán.
- **locked_by**: Customer đang giữ ghế.
- **locked_at**: Thời điểm bắt đầu giữ ghế.

## 4. Luồng nghiệp vụ chính

### Tạo và duyệt sự kiện

1. Organizer tạo sự kiện và thiết kế sơ đồ.
2. Sự kiện có trạng thái mặc định `pending`.
3. Admin xem danh sách pending events và duyệt sự kiện sang `approved` hoặc từ chối sang `rejected`.
4. Chỉ sự kiện `approved` mới hiển thị cho Customer.
5. Admin có thể sửa thông tin event được phép trước hoặc sau khi duyệt, nhưng không được sửa bank fields, master map fields, zones, số zones hoặc giá zones.
6. Admin chọn event lên trang chính bằng `is_featured`, `is_special` và `sort_order`.

### Đặt vé

1. Customer xem sơ đồ sự kiện.
2. Customer chọn ghế còn `available`.
3. Hệ thống lock ghế trong 10 phút bằng `status = locked`, `locked_by`, `locked_at`.
4. Customer checkout các ghế đã lock bằng mock payment.
5. Hệ thống tạo paid order, ticket QR và chuyển ghế sang `sold`.
6. Checkout chỉ thành công nếu tất cả ghế được chọn đang được chính customer lock và lock chưa hết hạn.
7. Vé đã bán không hỗ trợ hoàn tiền.
8. Vé hiển thị cho customer có trạng thái dẫn xuất:
   - `valid`: ticket còn hiệu lực, event chưa kết thúc.
   - `used`: ticket đã check-in.
   - `expired`: ticket vẫn `valid` trong DB nhưng event đã kết thúc.
   - `void`: ticket bị hủy hiệu lực theo policy nội bộ.

### Dọn ghế hết hạn lock

- Cronjob chạy mỗi 1 phút.
- Tìm các ghế `locked` quá 10 phút.
- Chuyển ghế về `available`.
- Xóa `locked_by` và `locked_at`.

## 5. Kiến trúc Laravel đề xuất

Dự án nên áp dụng mô hình phân tầng để dễ mở rộng và bảo trì.

### Controller Layer

- Chỉ nhận request, gọi service và trả response.
- Không chứa business logic phức tạp.
- Sử dụng Form Request để validate input.
- Trả response thống nhất qua BaseController hoặc API Response helper.

### Service Layer

- Chứa business logic chính.
- Xử lý luồng tạo sự kiện, sinh ghế, giữ ghế, thanh toán và phát hành vé.
- Điều phối transaction, lock row và dispatch job.
- Không trả trực tiếp Eloquent query phức tạp cho controller.

### Repository Layer

- Chịu trách nhiệm truy vấn dữ liệu qua Eloquent.
- Tách logic query phức tạp khỏi Service.
- Luôn cân nhắc eager loading để tránh N+1 query.

### DTO hoặc Data Objects

- Dùng cho các payload phức tạp như tạo event, tạo zones, checkout seats.
- Giúp service nhận dữ liệu rõ ràng thay vì truyền array rời rạc.

### API Resource Layer

- Dùng Laravel API Resource để chuẩn hóa dữ liệu trả về.
- Không trả trực tiếp toàn bộ model nếu không cần thiết.
- Ẩn các trường nhạy cảm hoặc trường nội bộ.

## 6. Thiết kế database chính

### users

Lưu tài khoản và phân quyền.

- `name`
- `email`
- `password`
- `role`: `admin`, `organizer`, `customer`
- `gender`
- `birthday`

### events

Lưu thông tin sự kiện và cấu hình map.

- `organizer_id`
- `name`
- `description`
- `venue`
- `starts_at`
- `ends_at`
- `status`: `pending`, `approved`, `rejected`
- `display_type`: `rectangular`, `stadium`
- `master_width`
- `master_length`
- `category`
- `thumbnail_url`
- `banner_url`
- `is_featured`
- `is_special`
- `sort_order`
- `ticket_sale_starts_at`
- `ticket_sale_ends_at`
- `bank_name`
- `bank_account_number`
- `bank_account_name`

### zones

Lưu các vùng trong sơ đồ sự kiện.

- `event_id`
- `name`
- `price`
- `color`
- `icon_url`
- `pos_x`
- `pos_y`
- `width`
- `length`
- `is_seating`

### seats

Lưu từng ghế được sinh từ seating zone.

- `zone_id`
- `row_index`
- `col_index`
- `status`: `available`, `locked`, `sold`
- `locked_by`
- `locked_at`

### orders

Lưu giao dịch mua vé.

- `order_code`
- `customer_id`
- `event_id`
- `subtotal_amount`
- `total_amount`
- `currency`
- `status`: `pending`, `paid`, `cancelled`, `expired`
- `payment_method`
- `payment_reference`
- `paid_at`
- `expires_at`

### tickets

Lưu vé điện tử QR Code.

- `ticket_code`
- `order_id`
- `event_id`
- `seat_id`
- `customer_id`
- `qr_code`
- `status`: `valid`, `used`, `void`
- `issued_at`
- `checked_in_at`

## 7. Best practices Laravel cho TicketRush

### Authentication và Authorization

- Sử dụng Laravel Sanctum cho API token authentication.
- Tạo middleware kiểm tra role: `admin`, `organizer`, `customer`.
- Sử dụng Policy để kiểm tra quyền sở hữu tài nguyên.
- Organizer chỉ được sửa event thuộc về mình.
- Customer chỉ được xem order và ticket của chính mình.
- Admin có quyền kiểm duyệt và quản trị toàn hệ thống.

### Validation

- Mỗi API ghi dữ liệu phải có Form Request riêng.
- Validate enum-like fields bằng `Rule::in()`.
- Validate map boundary:
  - `pos_x + width <= master_width`
  - `pos_y + length <= master_length`
- Không cho tạo zone có kích thước bằng 0.
- Không cho checkout ghế không thuộc cùng một event.
- Không cho checkout ghế đang `locked` bởi user khác hoặc đã `sold`.

### Transaction và concurrency

- Luồng giữ ghế và thanh toán bắt buộc dùng `DB::transaction()`.
- Khi chọn ghế phải query bằng `lockForUpdate()`.
- Không update trạng thái ghế ngoài transaction.
- Tạo order, tickets và cập nhật seats phải nằm trong cùng transaction khi xác nhận thanh toán.
- Luôn kiểm tra lại trạng thái ghế ngay trước khi chuyển sang `sold`.

### Queue, Job và Scheduler

- Dùng Scheduler để chạy job nhả ghế lock quá hạn mỗi phút.
- Các tác vụ nặng nên đưa vào queue:
  - Gửi email vé QR.
  - Sinh file PDF vé.
  - Gửi notification.
  - Tổng hợp báo cáo doanh thu.
- Job phải có retry, backoff và logging khi fail.

### API response standard

Tất cả API nên trả về JSON thống nhất:

```json
{
  "success": true,
  "message": "Request processed successfully.",
  "data": {}
}
```

Khi lỗi:

```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {}
}
```

### Eloquent và hiệu năng query

- Luôn eager load quan hệ thường dùng bằng `with()`.
- Tránh N+1 query khi load event map: event -> zones -> seats.
- Chỉ select các trường cần thiết khi render map.
- Thêm index cho các cột lọc nhiều:
  - `events.status`
  - `events.organizer_id`
  - `zones.event_id`
  - `seats.status`
  - `seats.locked_at`
  - `orders.customer_id`
  - `orders.event_id`
  - `tickets.customer_id`
  - `tickets.event_id`
- Dùng pagination cho danh sách events, orders và tickets.

### Security

- Không hardcode secret, token hoặc payment config trong source code.
- Dùng `.env` cho cấu hình nhạy cảm.
- Hash password bằng Laravel Hash.
- Không trả `password`, `remember_token`, token nội bộ qua API.
- Rate limit các API nhạy cảm:
  - Login.
  - Checkout.
  - Lock seat.
  - Validate QR.
- Ghi log các hành động quan trọng như approve event, checkout, check-in vé.

### QR Ticket

- QR code nên chứa token/code duy nhất, không chứa toàn bộ thông tin nhạy cảm.
- Khi scan QR, backend phải xác thực token trong database.
- Vé chỉ được check-in một lần.
- Nếu `checked_in_at` đã có giá trị thì không cho check-in lại.
- Không cho refund sau khi ticket đã phát hành theo policy của hệ thống.

### Testing

Cần ưu tiên test các luồng rủi ro cao:

- Admin duyệt hoặc từ chối event.
- Organizer tạo map, zones và sinh seats.
- Customer lock ghế thành công.
- Hai customer cùng chọn một ghế, chỉ một người được lock.
- Ghế lock quá hạn được release.
- Thanh toán thành công tạo order, ticket và chuyển seat sang `sold`.
- Không cho mua ghế đã `sold`.
- Không cho check-in QR hai lần.

### Coding convention

- Dùng strict typing cho PHP khi tạo class mới.
- Type hint đầy đủ tham số và kiểu trả về.
- Business logic không đặt trong Controller.
- Không dùng raw SQL nếu Eloquent/Query Builder đáp ứng được.
- Nếu bắt buộc raw SQL, phải binding parameter để tránh SQL injection.
- Dùng Enum class cho các trạng thái quan trọng nếu dự án đã sẵn sàng:
  - UserRole
  - EventStatus
  - DisplayType
  - SeatStatus
  - OrderStatus
  - TicketStatus

## 8. Gợi ý module backend

### Admin module

- Quản lý user.
- Xem danh sách event, bao gồm filter `pending`, `approved`, `rejected`.
- Duyệt hoặc từ chối pending event.
- Sửa thông tin event được phép; không sửa bank fields, map fields, số zones hoặc giá zones.
- Cấu hình event lên trang chính bằng `is_featured`, `is_special`, `sort_order`.
- Xem danh sách order/ticket.
- Xem log hệ thống.

### Organizer module

- CRUD event.
- Thiết kế map.
- CRUD zones.
- Auto-generate seats.
- Xem doanh thu và thống kê khách hàng.

### Customer module

- Browse events.
- View event detail and seat map.
- Lock seats.
- Checkout.
- View orders and tickets.
- Filter tickets by `valid`, `used`, `expired`, `void`.
- Sort tickets by issued time, event start time, created time, or status.

### Check-in module

- Scan QR.
- Validate ticket.
- Mark ticket as used.
- Prevent duplicate check-in.

## 9. Chính sách nghiệp vụ quan trọng

- Vé đã bán không được hoàn tiền.
- Ghế chỉ được giữ tối đa 10 phút.
- Chỉ event đã được duyệt mới được bán vé.
- Một seat chỉ được gắn với tối đa một ticket hợp lệ.
- Một ticket chỉ được check-in một lần.
- Organizer không được chỉnh sửa seat map khi event đã bắt đầu bán vé nếu thay đổi đó ảnh hưởng đến vé đã bán.
- Mọi thao tác thanh toán, phát hành vé và check-in phải được logging.

## 10. Quy tắc tài liệu API

- `.agents/api-doc.md` là source of truth cho FE dev khi tích hợp API.
- Mỗi khi backend thay đổi API, bắt buộc cập nhật `.agents/api-doc.md` trong cùng task.
- Các thay đổi cần cập nhật gồm route mới, route bị xóa, request body, response fields, validation rules, auth/role requirement, status code, error message, enum/status/category values, pagination và query parameters.
- Các file manual test như `.agents/auth-api-manual-test.md` và `.agents/event-api-manual-test.md` chỉ dùng để hỗ trợ test thủ công; nếu có mâu thuẫn thì ưu tiên `.agents/api-doc.md`.
