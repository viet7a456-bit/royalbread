# RoyalBread

RoyalBread là website bán bánh mì chảo được xây dựng bằng PHP theo mô hình MVC, kết nối cơ sở dữ liệu MySQL. Dự án có hai phần chính: trang khách hàng để xem món, đặt hàng, liên hệ, đăng nhập tài khoản; và trang quản trị để quản lý đơn hàng, thực đơn, khách hàng, tin nhắn, đánh giá và cài đặt hiển thị.

## Công nghệ sử dụng

- PHP 8.x
- MySQL / phpMyAdmin
- HTML, CSS, JavaScript
- PDO để kết nối cơ sở dữ liệu
- XAMPP cho môi trường chạy local

## Chức năng chính

- Trang chủ hiển thị banner, món nổi bật, thông tin cửa hàng và điều hướng nhanh.
- Trang thực đơn có danh mục món, tìm kiếm, chatbot gợi ý món và thêm món vào giỏ hàng.
- Giỏ hàng cho phép cập nhật số lượng, tính phí ship, chọn phương thức thanh toán và tạo đơn.
- Tài khoản khách hàng hỗ trợ đăng ký, đăng nhập, xem lịch sử đơn, lưu món yêu thích, đánh giá món và live chat.
- Khu quản trị hỗ trợ xem dashboard, quản lý đơn hàng, quản lý menu, khách hàng, tin nhắn, đánh giá, doanh thu và cài đặt website.

## Cấu trúc thư mục

- `index.php`: file chạy đầu vào của website.
- `app/controllers`: xử lý luồng chức năng theo mô hình MVC.
- `app/models`: truy vấn và xử lý dữ liệu MySQL.
- `app/views`: giao diện trang khách hàng, trang đăng nhập và trang admin.
- `app/core`: router, controller nền, session và kết nối database.
- `assets/css`: giao diện CSS của website.
- `assets/js`: JavaScript cho tìm kiếm, giỏ hàng, chatbot, bản đồ và tương tác giao diện.
- `assets/images`: logo, banner và ảnh upload dùng trong website.
- `database/royalbread.sql`: file database dùng để import vào phpMyAdmin.

## Cài đặt trên XAMPP

1. Copy thư mục dự án vào `F:\xampp\htdocs\royalbread`.
2. Mở XAMPP và bật Apache, MySQL.
3. Vào phpMyAdmin, tạo database tên `royalbread_db`.
4. Import file `database/royalbread.sql`.
5. Nếu chạy local bằng cấu hình mặc định của XAMPP thì không cần sửa `.env`.
6. Truy cập website tại `http://localhost/royalbread`.

## Đẩy lên host InfinityFree

1. Vào File Manager của host và mở thư mục `htdocs`.
2. Upload trực tiếp các thư mục/file sau vào đúng `htdocs`: `app`, `assets`, `database`, `.htaccess`, `.env`, `index.php`, `README.md`.
3. Không upload cả thư mục `royalbread` vào `htdocs/royalbread`, vì khi đó domain gốc sẽ không thấy `index.php` và rất dễ báo `403 Forbidden`.
4. Vào phpMyAdmin trên host, chọn đúng database đã được cấp.
5. Import file `database/royalbread.sql`.
6. Mở file `.env` trên host và kiểm tra các thông tin `ROYALBREAD_DB_HOST`, `ROYALBREAD_DB_NAME`, `ROYALBREAD_DB_USER`, `ROYALBREAD_DB_PASSWORD`.
7. Truy cập domain để kiểm tra trang chủ, thực đơn, giỏ hàng và trang admin.

## Tài khoản kiểm tra

- Admin: truy cập `/admin/login`.
- Tên đăng nhập admin: `admin`.
- Mật khẩu admin mặc định: `admin123`.
- Khách hàng: có thể đăng ký tài khoản mới tại `/customer/register`.
- Tên đăng nhập khách : `ngoviet213`
- Mật khẩu khách : `123456`

## Ghi chú

- Nếu domain vừa upload xong mà báo `403 Forbidden`, kiểm tra lại ngay xem `index.php` có đang nằm trực tiếp trong `htdocs` hay không.
- File `.env` cần có trên host, nếu thiếu thì website sẽ không kết nối đúng database.
