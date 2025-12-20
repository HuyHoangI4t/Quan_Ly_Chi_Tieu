---


# HƯỚNG DẪN CÀI ĐẶT & SỬ DỤNG SMARTSPENDING

---

## 1. Chuẩn bị hệ thống

- Windows 10/11 (khuyến nghị)
- Đã cài đặt: PHP >= 7.4, MySQL >= 5.7, Composer, Git
  - Tải PHP: https://windows.php.net/download
  - Tải MySQL: https://dev.mysql.com/downloads/installer/
  - Tải Composer: https://getcomposer.org/download/
  - Tải Git: https://git-scm.com/download/win

---

## 2. Tải và cài đặt mã nguồn

1. Mở Command Prompt (CMD)
2. Di chuyển đến thư mục bạn muốn lưu dự án, ví dụ:
  ```
  cd C:\xampp\htdocs
  ```
3. Clone mã nguồn:
  ```
  git clone https://github.com/HuyHoangI4t/Quan_Ly_Chi_Tieu.git
  cd Quan_Ly_Chi_Tieu
  ```
4. Chạy file tự động cài đặt:
  ```
  install.bat
  ```
5. Làm theo hướng dẫn trên màn hình:
  - Nhập thông tin kết nối MySQL (user, password, tên database)
  - Script sẽ tự động tạo database, import cấu trúc bảng và dữ liệu mẫu nếu bạn chọn

---

## 3. Cấu hình file .env

1. Copy file `.env.example` thành `.env`:
  ```
  copy .env.example .env
  ```
2. Mở file `.env` bằng Notepad hoặc VS Code
3. Sửa các thông tin kết nối database cho đúng với thông tin bạn vừa nhập:
  - DB_HOST, DB_NAME, DB_USER, DB_PASS

---

## 4. Khởi động dịch vụ

1. Đảm bảo MySQL và Apache/XAMPP đã chạy
2. Truy cập hệ thống tại địa chỉ:
  ```
  http://localhost/Quan_Ly_Chi_Tieu/public
  ```

---

## 5. Đăng nhập & sử dụng hệ thống

### Đăng nhập
- Đăng nhập bằng tài khoản đã có hoặc đăng ký mới
- Để tạo tài khoản admin, thêm user với trường `role` là `admin` trong bảng `users` (dùng phpMyAdmin hoặc lệnh SQL)

### Giao diện & chức năng
- Sau khi đăng nhập, bạn sẽ thấy giao diện chính với các chức năng:
  - Quản lý thu nhập, chi tiêu, ngân sách
  - Quản lý mục tiêu tài chính
  - Giao dịch định kỳ
  - Báo cáo trực quan bằng biểu đồ
  - Quản lý tài khoản, đổi mật khẩu

#### Một số thao tác thường gặp
- Thêm giao dịch: Vào mục Giao dịch > Thêm mới > Nhập thông tin > Lưu
- Xem báo cáo: Vào mục Báo cáo > Chọn loại > Chọn thời gian > Xem biểu đồ
- Tạo ngân sách: Vào mục Ngân sách > Thêm ngân sách > Chọn danh mục > Nhập số tiền > Lưu
- Đặt mục tiêu: Vào mục Mục tiêu > Thêm mục tiêu > Nhập thông tin > Lưu

---

## 6. Lưu ý & hỗ trợ

- Không commit file `.env` lên git
- Nếu gặp lỗi, kiểm tra lại các bước cài đặt, thông tin kết nối hoặc gửi câu hỏi lên [github issues](https://github.com/HuyHoangI4t/Quan_Ly_Chi_Tieu/issues)
- Để import lại dữ liệu mẫu, có thể chạy lại script hoặc dùng lệnh:
  ```
  mysql -uYOUR_USER -pYOUR_PASS YOUR_DB < database/data_test.sql
  ```

---

## 7. Đóng góp & phát triển

- Đóng góp mã nguồn, báo lỗi hoặc đề xuất tính năng mới qua [github issues](https://github.com/HuyHoangI4t/Quan_Ly_Chi_Tieu/issues)

---

Chúc bạn cài đặt và sử dụng thành công SmartSpending!
