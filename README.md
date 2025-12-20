#
---

## Hướng dẫn chi tiết cho người mới

Nếu bạn là người mới, hãy xem file [HUONG_DAN_SU_DUNG.md](HUONG_DAN_SU_DUNG.md) để biết hướng dẫn từng bước cài đặt và sử dụng hệ thống SmartSpending.

---


# SmartSpending - Quản Lý Chi Tiêu Cá Nhân

## Giới thiệu
> Ứng dụng giúp bạn quản lý thu nhập, chi tiêu, ngân sách, mục tiêu tài chính và báo cáo trực quan.

## Tính năng chính
- Quản lý thu nhập, chi tiêu, ngân sách
- Báo cáo trực quan bằng biểu đồ
> `vendor/` chứa các thư viện bên thứ ba. `app/` là mã nguồn chính. `public/` là webroot.

# SmartSpending - Quản Lý Chi Tiêu Cá Nhân

## Giới thiệu

SmartSpending là hệ thống giúp bạn quản lý thu nhập, chi tiêu, ngân sách, mục tiêu tài chính và báo cáo trực quan. Ứng dụng phù hợp cho cá nhân hoặc hộ gia đình muốn kiểm soát tài chính hiệu quả.

---

## Tính năng nổi bật

- Quản lý thu nhập, chi tiêu, ngân sách
- Báo cáo trực quan bằng biểu đồ
- Quản lý mục tiêu tài chính
- Giao dịch định kỳ
- Phân quyền người dùng (admin, user)

---

## Hướng dẫn cài đặt
---

## Cài đặt tự động

Bạn có thể sử dụng file `install.bat` để tự động cài đặt các thư viện PHP:

1. Mở Command Prompt tại thư mục dự án hoặc nơi bạn muốn clone mã nguồn.
2. Chạy lệnh:
   ```
   install.bat
   ```
3. Script sẽ hướng dẫn bạn:
   - Tải/cài đặt PHP, MySQL, Composer nếu chưa có
   - Clone mã nguồn về nếu bạn chưa có
   - Cài đặt thư viện PHP qua Composer
   - Nhập thông tin kết nối MySQL (user, password, tên database)
   - Tự động tạo database và import cấu trúc bảng từ `database/schema.sql`
   - (Tùy chọn) Import dữ liệu mẫu từ `database/data_test.sql`
4. Sau khi script chạy xong:
   - Copy file `.env.example` thành `.env`
   - Sửa thông tin kết nối database trong file `.env` cho đúng với thông tin bạn vừa nhập
   - Truy cập `http://localhost/Quan_Ly_Chi_Tieu/public` trên trình duyệt để sử dụng hệ thống

> Lưu ý: Script này chỉ hỗ trợ tự động hóa trên Windows và yêu cầu bạn đã cài đặt PHP, MySQL, Composer, Git. Nếu chưa có, hãy tải và cài đặt theo hướng dẫn của script.

1. **Clone dự án về máy:**
   ```bash
   git clone https://github.com/HuyHoangI4t/Quan_Ly_Chi_Tieu.git
   ```
2. **Cài đặt môi trường:**
   - PHP >= 7.4
   - MySQL >= 5.7
   - Composer
3. **Cài đặt thư viện:**
   ```bash
   composer install
   ```
4. **Tạo database và import dữ liệu:**
   - Tạo database mới trong MySQL
   - Import file `database/schema.sql` để tạo bảng
   - (Tùy chọn) Import thêm file `database/data_test.sql` nếu cần dữ liệu mẫu
5. **Cấu hình kết nối database:**
   - Copy file `.env.example` thành `.env`
   - Chỉnh sửa thông tin kết nối database trong file `.env`

---

## Sử dụng hệ thống

- Truy cập hệ thống tại địa chỉ:
  ```
  http://localhost/Quan_Ly_Chi_Tieu/public
  ```
- Đăng nhập bằng tài khoản đã tạo.
- Để tạo tài khoản admin, thêm user với trường `role` là `admin` trong bảng `users`.

---

## Cấu hình & Lưu ý

- Thông tin cấu hình lưu ở file `.env`, KHÔNG commit file này lên git.
- Nếu gặp lỗi hoặc cần hỗ trợ, vui lòng gửi về [github issues](https://github.com/HuyHoangI4t/Quan_Ly_Chi_Tieu/issues).

---

## Cấu trúc dự án

```
composer.json         // Cấu hình các thư viện PHP
LICENSE               // Giấy phép sử dụng mã nguồn
README.md             // Tài liệu hướng dẫn
app/                  // Thư mục mã nguồn chính
	Controllers/      // Bộ điều khiển (chia theo Admin, Auth, User)
		Admin/        // Chức năng quản trị
		Auth/         // Đăng nhập, xác thực
		User/         // Chức năng người dùng
	Core/             // Các lớp lõi hệ thống (App, Kết nối DB, Quản lý session...)
	Middleware/       // Lớp trung gian (bảo vệ CSRF, kiểm tra đăng nhập...)
	Models/           // Các mô hình dữ liệu (Ngân sách, Giao dịch, Người dùng...)
	Services/         // Các dịch vụ xử lý logic (Dashboard, Kiểm tra dữ liệu...)
config/               // Cấu hình hệ thống, database
database/             // File khởi tạo và dữ liệu mẫu cho database
images/               // Ảnh sử dụng trong hệ thống
public/               // Thư mục webroot, chứa file index.php, CSS, JS
	css/              // Giao diện, style cho các trang
	js/               // Các file Javascript cho chức năng
	shared/           // JS/CSS dùng chung
resources/            // Giao diện view (admin, user, auth, partials...)
storage/              // Lưu trữ log, file tạm
vendor/               // Thư viện bên thứ ba cài qua Composer
```

> Lưu ý:
> - `vendor/` chứa các thư viện bên ngoài, không chỉnh sửa trực tiếp.
> - `app/` là nơi phát triển các chức năng chính của hệ thống.
> - `public/` là thư mục truy cập từ trình duyệt (webroot).

Nếu bạn cần danh sách chi tiết từng file, hãy yêu cầu để nhận thêm tài liệu hoặc file `STRUCTURE.md`.

---

## Đóng góp

Mọi ý kiến đóng góp, báo lỗi hoặc đề xuất tính năng mới vui lòng gửi về [github issues](https://github.com/HuyHoangI4t/Quan_Ly_Chi_Tieu/issues).

---

## License

MIT
