# SmartSpending - Quản Lý Chi Tiêu Cá Nhân

## Giới thiệu
SmartSpending là hệ thống quản lý chi tiêu cá nhân, hỗ trợ theo dõi thu nhập, chi tiêu, ngân sách, mục tiêu tài chính và báo cáo trực quan.

## Tính năng chính
- Quản lý thu nhập, chi tiêu, ngân sách
- Báo cáo trực quan bằng biểu đồ
- Quản lý mục tiêu tài chính
- Giao dịch định kỳ
- Phân quyền người dùng (admin, user)

## Cài đặt
1. Clone dự án về máy:
	```
	git clone https://github.com/HuyHoangI4t/Quan_Ly_Chi_Tieu.git
	```
2. Cài đặt PHP >= 7.4, MySQL >= 5.7, Composer
3. Cài đặt thư viện:
	```
	composer install
	```
4. Tạo database và import file `database/schema.sql` và `database/data.sql`
5. Copy file `.env.example` thành `.env` và chỉnh sửa thông tin kết nối database

## Cấu hình
- Thông tin cấu hình lưu ở file `.env`
- Không commit file `.env` lên git

## Sử dụng
Truy cập trang web tại:
```
http://localhost/Quan_Ly_Chi_Tieu/public
```

## Tài khoản admin
- Để tạo tài khoản admin, thêm user với trường `role` là `admin` trong bảng `users`

## Đóng góp
Mọi ý kiến đóng góp vui lòng gửi về [github issues](https://github.com/HuyHoangI4t/Quan_Ly_Chi_Tieu/issues)

## License
MIT
