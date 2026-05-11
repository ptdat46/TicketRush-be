DỰ ÁN: TICKETRUSH - HỆ THỐNG ĐẶT VÉ ONLINE (BTL INT3306)

1. VAI TRÒ HỆ THỐNG:
- Admin: Duyệt đơn đăng ký tổ chức sự kiện từ các Organizer.
- Organizer (Người tạo sự kiện): Thiết kế sơ đồ, quản lý vé, xem báo cáo doanh thu/thị hiếu.
- Customer: Xem sơ đồ, đặt chỗ, thanh toán (giả lập), quản lý vé QR.

2. LOGIC THIẾT KẾ SƠ ĐỒ (MAP DESIGN):
- Master Map: Mỗi sự kiện chọn 1 trong 3 mẫu (Rectangular, Arc, Stadium). Quy định kích thước tổng (Width x Length) theo đơn vị ô lưới (slots).
- Zones: Người tạo sự kiện vẽ các vùng (Zone) nằm trong phạm vi Master Map. 
  + Định vị: Mỗi Zone lưu tọa độ (pos_x, pos_y) và kích thước (width, length) tương đối so với Master Map.
  + Phân loại: Zone ghế ngồi (Seating) và Zone lối đi (Aisle).
  + Thuộc tính Zone: Tên, Giá vé, Màu sắc hiển thị, Icon đại diện.
- Seats: Hệ thống tự động sinh dữ liệu ghế dựa trên (width x length) của từng "Zone ghế ngồi". Trạng thái mặc định là 'available'.

3. QUY TRÌNH NGHIỆP VỤ CHÍNH:
- Đặt vé: Chọn ghế -> Lock ghế (10p) -> Thanh toán (Bấm xác nhận) -> Xuất vé QR -> Chuyển trạng thái 'sold'.
- Chống tranh chấp: Sử dụng Database Transaction & Row Locking (FOR UPDATE) khi người dùng click giữ chỗ.
- Chính sách: Vé đã mua (Sold) không được phép hoàn tiền (No Refund).
- Dọn dẹp: Cronjob chạy 1 phút/lần để nhả các ghế 'locked' quá hạn.