# 💰 SmartSpending - Quản Lý Chi Tiêu Cá Nhân

[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

**SmartSpending** là ứng dụng web quản lý chi tiêu cá nhân được xây dựng bằng PHP với kiến trúc Custom MVC. Ứng dụng giúp người dùng theo dõi thu chi, lập ngân sách, phân tích báo cáo tài chính một cách trực quan và dễ dàng.

---

## 🌟 Tính Năng Nổi Bật

### ✅ Đã Triển Khai

#### 🏗️ Kiến Trúc & Code Quality
- **Custom MVC Architecture**: Kiến trúc MVC tự xây dựng, rõ ràng và dễ bảo trì
- **Service Layer**: Tách biệt logic nghiệp vụ (FinancialUtils, Validator)
- **Single Responsibility Principle**: Mỗi class có một trách nhiệm duy nhất
- **PSR-4 Autoloading**: Tự động load class theo chuẩn PSR-4
- **Standardized API Response**: Format JSON thống nhất cho tất cả API endpoints

#### 🔒 Bảo Mật
- **CSRF Protection**: Bảo vệ toàn bộ POST requests khỏi tấn công CSRF
- **Data Validation & Sanitization**: Validate và làm sạch tất cả input người dùng
- **Password Hashing**: Mã hóa mật khẩu với bcrypt
- **Session Management**: Quản lý phiên đăng nhập an toàn
- **SQL Injection Prevention**: Sử dụng PDO Prepared Statements

#### 💸 Quản Lý Giao Dịch
- Thêm, sửa, xóa giao dịch thu/chi
- Lọc theo thời gian (tuần, tháng, năm) và danh mục
- Phân trang danh sách giao dịch
- Xuất dữ liệu ra file CSV

#### 📊 Dashboard & Báo Cáo
- Tổng quan thu nhập, chi tiêu, số dư
- Biểu đồ Line Chart (xu hướng 3 tháng)
- Biểu đồ Pie Chart (phân bổ chi tiêu theo danh mục)
- Hiển thị giao dịch gần đây
- Tỷ lệ tiết kiệm (Savings Rate)

#### 💰 Hệ Thống Ngân Sách (BUDGETS)
- **CRUD đầy đủ**: Tạo, xem, sửa, xóa ngân sách
- **Real-time Calculation**: Tính toán tự động số tiền đã chi và còn lại
- **Budget Status**: 
  - 🟢 Safe (< 80%)
  - 🟡 Warning (80-99%)
  - 🔴 Exceeded (≥ 100%)
- **Summary Dashboard**: Tổng quan tất cả ngân sách theo tháng
- **Progress Bars**: Hiển thị trực quan tiến độ chi tiêu

#### 🔄 Giao Dịch Định Kỳ (RECURRING TRANSACTIONS)
- Tạo giao dịch lặp lại (hàng ngày, tuần, tháng, năm)
- Tự động tạo giao dịch thực tế khi đến kỳ hạn
- Quản lý ngày bắt đầu và kết thúc
- Tạm dừng/Kích hoạt giao dịch định kỳ

#### 📁 Danh Mục Tùy Chỉnh
- Danh mục mặc định cho tất cả người dùng
- Tạo danh mục riêng theo nhu cầu cá nhân
- Phân loại thu nhập và chi tiêu
- Không thể xóa danh mục đang được sử dụng

#### 👤 Quản Lý Profile
- Cập nhật thông tin cá nhân
- Đổi mật khẩu
- Xóa tất cả dữ liệu
- Xuất dữ liệu

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

## 📁 Cấu Trúc Dự Án (Custom MVC)

```
Quan_Ly_Chi_Tieu/
├── app/
│   ├── controllers/         # Controllers xử lý request
│   │   ├── Budgets.php
│   │   ├── Dashboard.php
│   │   ├── Profile.php
│   │   ├── Reports.php
│   │   └── Transactions.php
│   ├── core/               # Core MVC classes
│   │   ├── ApiResponse.php  # Standardized API responses
│   │   ├── App.php          # Application router
│   │   ├── ConnectDB.php    # Database connection
│   │   ├── Controllers.php  # Base controller
│   │   └── Views.php        # View renderer
│   ├── middleware/         # Middleware layer
│   │   └── CsrfProtection.php  # CSRF token validation
│   ├── models/             # Models (Business Logic)
│   │   ├── Budget.php
│   │   ├── Category.php
│   │   ├── RecurringTransaction.php
│   │   ├── Transaction.php
│   │   └── User.php
│   ├── services/           # Service layer (utilities)
│   │   ├── FinancialUtils.php  # Financial calculations
│   │   └── Validator.php       # Input validation
│   └── views/              # Views (HTML/PHP templates)
│       ├── budgets/
│       ├── dashboard/
│       ├── partials/       # Reusable components (header, footer)
│       ├── profile/
│       ├── reports/
│       └── transactions/
├── config/
│   └── database.php        # Database configuration
├── database/
│   ├── migrations/         # Database schema changes
│   │   ├── 001_add_recurring_transactions.sql
│   │   └── 002_add_goals_table.sql
│   ├── schema.sql          # Complete database schema (NEW)
│   ├── sample_data.sql     # Sample data (UPDATED)
│   └── README.md           # Database documentation
├── docs/
│   ├── API.md              # API Documentation
│   └── images/             # Screenshots
├── public/                 # Web root (index.php)
│   ├── css/                # Stylesheets
│   ├── js/                 # JavaScript files
│   │   ├── budgets.js
│   │   ├── dashboard.js
│   │   ├── profile.js
│   │   ├── reports.js
│   │   └── transactions.js
│   └── images/             # Static images
├── routes/                 # Route definitions
├── vendor/                 # Composer dependencies
├── composer.json           # Composer configuration
└── README.md              # This file
```

---

## 🚀 Hướng Dẫn Cài Đặt

### Yêu Cầu Hệ Thống
- PHP 7.4 hoặc cao hơn
- MySQL 5.7+ hoặc MariaDB 10.4+
- Apache/Nginx với mod_rewrite enabled
- Composer (recommended)
- XAMPP/WAMP/LAMP (cho môi trường local)

### Các Bước Cài Đặt

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

## 📖 Hướng Dẫn Sử Dụng

### 1. Dashboard
- Xem tổng quan thu chi, số dư hiện tại
- Theo dõi xu hướng thu chi qua biểu đồ
- Xem phân bổ chi tiêu theo danh mục

### 2. Quản Lý Giao Dịch
- **Thêm giao dịch**: Click "Thêm giao dịch", nhập thông tin
- **Lọc**: Chọn tháng và danh mục để lọc
- **Sửa/Xóa**: Click icon tương ứng trong bảng

### 3. Ngân Sách
- **Tạo ngân sách**: Click "Thêm Ngân sách"
- **Theo dõi**: Xem thanh tiến độ màu sắc:
  - 🟢 Xanh: An toàn (< 80%)
  - 🟡 Vàng: Cảnh báo (80-99%)
  - 🔴 Đỏ: Vượt mức (≥ 100%)
- **Điều chỉnh**: Sửa hạn mức khi cần thiết

### 4. Giao Dịch Định Kỳ
- Tạo cho các khoản thu chi cố định (lương, tiền nhà, điện nước...)
- Hệ thống tự động tạo giao dịch đúng kỳ hạn
- Có thể tạm dừng hoặc kết thúc bất kỳ lúc nào

### 5. Báo Cáo
- Xem báo cáo chi tiết theo thời gian
- Phân tích xu hướng chi tiêu
- So sánh các kỳ trước

---

## 🔐 Bảo Mật

### CSRF Protection
Tất cả POST requests đều được bảo vệ bởi CSRF token:
```javascript
// Frontend usage
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

fetch('/api/endpoint', {
  method: 'POST',
  headers: {
    'X-CSRF-Token': csrfToken
  },
  body: JSON.stringify({ csrf_token: csrfToken, ...data })
});
```

### Data Validation
Tất cả input đều được validate và sanitize:
```php
$validator = new Validator();
if (!$validator->validateTransaction($data)) {
    ApiResponse::validationError($validator->getErrors());
}
```

---

## 📚 API Documentation

Xem chi tiết tại: [docs/API.md](docs/API.md)

**Ví dụ quick start:**
```javascript
// Add transaction
const response = await fetch('/transactions/api_add', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-Token': csrfToken
  },
  body: JSON.stringify({
    category_id: 1,
    amount: 50000,
    date: '2025-12-01',
    description: 'Lunch',
    csrf_token: csrfToken
  })
});
```

---

## 🎨 Screenshots

_(Thêm screenshots của ứng dụng vào đây)_

- Dashboard
- Transactions List
- Budgets Management
- Reports

---

## 🧪 Testing

### Manual Testing
1. Tạo tài khoản mới
2. Thêm các giao dịch mẫu
3. Tạo ngân sách cho các danh mục
4. Kiểm tra các biểu đồ và báo cáo

### Security Testing
- Test CSRF protection bằng cách gửi request không có token
- Test SQL injection với các input đặc biệt
- Test XSS với script tags trong description

---

## 🚧 Roadmap & Future Features

### Đang phát triển
- [ ] Dark Mode UI
- [ ] AJAX pagination for Transactions
- [ ] Input masking for amount fields
- [ ] Loading spinners for all API calls
- [ ] Dynamic filters for Reports

### Kế hoạch tương lai
- [ ] Multi-currency support
- [ ] Goals & Savings targets
- [ ] Mobile app (React Native / Flutter)
- [ ] Email notifications
- [ ] Backup & Restore
- [ ] Two-factor authentication (2FA)

---

## 👨‍💻 Đóng Góp

Contributions are welcome! Please follow these steps:

1. Fork the project
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 👤 Tác Giả

**HUYHOANG**
- Email: huyhoangpro187@gmail.com
- GitHub: [@HuyHoangI4t](https://github.com/HuyHoangI4t)

---

## 🙏 Acknowledgments

- Bootstrap team for the awesome CSS framework
- Chart.js team for beautiful charts
- PHP community for excellent documentation

---

## 📞 Liên Hệ & Hỗ Trợ

Nếu bạn gặp vấn đề hoặc có câu hỏi:
- Tạo [Issue](https://github.com/HuyHoangI4t/Quan_Ly_Chi_Tieu/issues) trên GitHub
- Email: huyhoangpro187@gmail.com

---

**Happy Budgeting! 💰✨**
