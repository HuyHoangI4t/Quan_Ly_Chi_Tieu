# 💰 Quản Lý Chi Tiêu

Ứng dụng quản lý chi tiêu cá nhân với PHP MVC, giúp theo dõi thu chi, lập ngân sách theo phương pháp 6 lọ, và phân tích báo cáo tài chính.

---

## ✨ Tính Năng

- **Dashboard**: Tổng quan thu chi, biểu đồ xu hướng 3 tháng, phân bổ chi tiêu
- **Giao dịch**: Thêm/sửa/xóa, lọc theo thời gian và danh mục, xuất CSV
- **Ngân sách 6 Lọ**: Quản lý theo phương pháp T. Harv Eker (NEC 55%, FFA 10%, EDU 10%, LTSS 10%, PLAY 10%, GIVE 5%)
- **Mục tiêu**: Thiết lập và theo dõi tiến độ tiết kiệm
- **Báo cáo**: Phân tích chi tiết theo tháng/năm
- **Bảo mật**: CSRF Protection, Password Hashing, SQL Injection Prevention

---

## 🛠️ Công Nghệ Sử Dụng

### Backend
- **PHP 7.4+**: Ngôn ngữ lập trình chính
- **PDO**: Database access với Prepared Statements
- **Custom MVC**: Kiến trúc MVC tự xây dựng
- **Composer**: Dependency management & PSR-4 autoloading

### Frontend
- **HTML5 & CSS3**: Giao diện người dùng
- **Bootstrap 5**: CSS Framework responsive
- **JavaScript (Vanilla)**: Logic frontend
- **Chart.js**: Biểu đồ trực quan
- **AJAX/Fetch API**: Giao tiếp với backend không reload trang

### Database
- **MySQL 5.7+** / **MariaDB 10.4+**: Lưu trữ dữ liệu
- **InnoDB Engine**: Hỗ trợ Foreign Keys và Transactions

---

## 📁 Cấu Trúc

```
app/
├── controllers/    # Budgets, Dashboard, Goals, Profile, Reports, Transactions
├── core/          # App, ApiResponse, ConnectDB, Controllers, Views
├── middleware/    # Middleware (Auth, Guest, CSRF)
├── models/        # Category, Goal, Transaction, User
├── services/      # FinancialUtils, Validator
└── views/         # budgets.php, dashboard.php, goals.php, ...
config/            # database.php
database/          # full_schema.sql, test_data_oct_nov.sql
public/            # index.php, css/, js/
vendor/            # Composer autoload
``` Các Bước Cài Đặt

#### 1. Clone hoặc Download dự án
```bash
git clone https://github.com/HuyHoangI4t/Quan_Ly_Chi_Tieu.git
cd Quan_Ly_Chi_Tieu
```

#### 2. Cài đặt dependencies (nếu có Composer)
```bash
composer install
# Hoặc nếu chỉ cần update autoload
composer dump-autoload
```

#### 3. Cấu hình Database

**Tạo database:**
```sql
CREATE DATABASE quan_ly_chi_tieu CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

**Import schema:**
```bash
# Import complete schema (bao gồm tables, views, procedures, triggers)
mysql -u root -p quan_ly_chi_tieu < database/schema.sql

# (Optional) Import sample data
mysql -u root -p quan_ly_chi_tieu < database/sample_data.sql
```

**Hoặc import từ XAMPP phpMyAdmin:**
1. Mở phpMyAdmin
2. Tạo database `quan_ly_chi_tieu`
3. Import file `database/schema.sql`
4. (Optional) Import file `database/sample_data.sql`

**Cập nhật config:**

Sửa file `config/database.php`:
```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'quan_ly_chi_tieu');
define('DB_USER', 'root');
define('DB_PASS', '');  // Mật khẩu MySQL của bạn
define('DB_CHARSET', 'utf8mb4');
```

#### 4. Cấu hình Virtual Host (Optional - Recommended)

**Cho Apache (XAMPP):**

Thêm vào `httpd-vhosts.conf`:
```apache
<VirtualHost *:80>
    ServerName smartspending.local
    DocumentRoot "C:/xampp/htdocs/Quan_Ly_Chi_Tieu/public"
    
    <Directory "C:/xampp/htdocs/Quan_Ly_Chi_Tieu/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Thêm vào `C:\Windows\System32\drivers\etc\hosts`:
```
127.0.0.1    smartspending.local
```

#### 5. Khởi động Server

**Với XAMPP:**
- Start Apache và MySQL
- Truy cập: `http://smartspending.local` hoặc `http://localhost/Quan_Ly_Chi_Tieu/public`

**Với PHP Built-in Server:**
```bash
cd public
php -S localhost:8000
```
Truy cập: `http://localhost:8000`

#### 6. Đăng nhập

**Tài khoản mặc định (nếu import sample data):**
- Username: `testuser`
- Password: `password123`

---

## 📖 Sử Dụng

1. **Dashboard**: Xem tổng quan, biểu đồ xu hướng, giao dịch gần đây
2. **Giao dịch**: Thêm/sửa/xóa, lọc theo tháng/danh mục
3. **Ngân sách 6 Lọ**: Phân bổ thu nhập theo 6 mục đích, theo dõi tiến độ
4. **Mục tiêu**: Thiết lập mục tiêu tiết kiệm, nạp tiền vào mục tiêu
5. **Báo cáo**: Phân tích chi tiết theo tháng/năm

---



---

---

**HUYHOANG** - huyhoangpro187@gmail.com - [@HuyHoangI4t](https://github.com/HuyHoangI4t)
