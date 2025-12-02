# 📁 Cấu Trúc Dự Án SmartSpending

## 🎯 Tổng Quan
SmartSpending là ứng dụng quản lý chi tiêu cá nhân được xây dựng theo mô hình MVC với PHP thuần.

---

## 📂 Cấu Trúc Thư Mục

```
SmartSpending/
├── 📁 app/                          # Application core
│   ├── 📁 controllers/              # Controllers - Xử lý logic nghiệp vụ
│   │   ├── Budgets.php             # Quản lý ngân sách
│   │   ├── Dashboard.php           # Trang tổng quan
│   │   ├── Goals.php               # Quản lý mục tiêu
│   │   ├── Login_signup.php        # Xác thực người dùng
│   │   ├── Profile.php             # Quản lý hồ sơ
│   │   ├── Reports.php             # Báo cáo & thống kê
│   │   └── Transactions.php        # Quản lý giao dịch
│   │
│   ├── 📁 core/                     # Core framework
│   │   ├── ApiResponse.php         # Helper format JSON response
│   │   ├── App.php                 # Application router
│   │   ├── ConnectDB.php           # Database connection
│   │   ├── Controllers.php         # Base controller class
│   │   └── Views.php               # View renderer
│   │
│   ├── 📁 middleware/               # Middleware layer
│   │   └── CsrfProtection.php      # CSRF token validation
│   │
│   ├── 📁 models/                   # Models - Data access layer
│   │   ├── Budget.php              # Budget model
│   │   ├── Category.php            # Category model
│   │   ├── Goal.php                # Goal model
│   │   ├── RecurringTransaction.php # Recurring transaction model
│   │   ├── Transaction.php         # Transaction model
│   │   └── User.php                # User model
│   │
│   ├── 📁 services/                 # Business logic services
│   │   ├── FinancialUtils.php      # Financial calculations
│   │   └── Validator.php           # Input validation & sanitization
│   │
│   └── 📁 views/                    # View templates
│       ├── 📁 budgets/             # Budget views
│       │   └── index.php
│       ├── 📁 dashboard/           # Dashboard views
│       │   └── index.php
│       ├── 📁 goals/               # Goal views
│       │   └── index.php
│       ├── 📁 login_signup/        # Auth views
│       │   └── index.php
│       ├── 📁 partials/            # Shared components
│       │   ├── footer.php
│       │   └── header.php
│       ├── 📁 profile/             # Profile views
│       │   └── index.php
│       ├── 📁 reports/             # Report views
│       │   └── index.php
│       └── 📁 transactions/        # Transaction views
│           └── index.php
│
├── 📁 config/                       # Configuration files
│   └── database.php                # Database configuration
│
├── 📁 database/                     # Database files
│   ├── 📁 migrations/              # SQL migration scripts
│   │   ├── 001_add_recurring_and_budgets.sql
│   │   └── 002_add_goals_table.sql
│   ├── quan_ly_chi_tieu.sql       # Main database schema
│   └── sample_data_october_2025.sql # Sample data
│
├── 📁 docs/                         # Documentation
│   ├── 📁 guides/                  # User & developer guides
│   │   ├── FINAL_COMPLETION_SUMMARY.md
│   │   ├── GOALS_INSTALLATION_GUIDE.md
│   │   ├── QUICK_START.md
│   │   └── UPGRADE_SUMMARY.md
│   ├── 📁 images/                  # Documentation images
│   ├── API.md                      # API documentation
│   └── MIGRATION_GUIDE.md          # Migration guide
│
├── 📁 public/                       # Public assets (DocumentRoot)
│   ├── 📁 css/                     # Stylesheets
│   │   ├── budgets.css
│   │   ├── dashboard.css
│   │   ├── goals.css
│   │   ├── login_signup.css
│   │   ├── profile.css
│   │   ├── reports.css
│   │   ├── style.css              # Main styles
│   │   └── transactions.css
│   │
│   ├── 📁 images/                  # Public images
│   │
│   ├── 📁 js/                      # JavaScript files
│   │   ├── app.js                 # Shared utilities
│   │   ├── budgets.js
│   │   ├── budgets_new.js
│   │   ├── dashboard.js
│   │   ├── goals.js
│   │   ├── input-masking.js       # Amount formatting
│   │   ├── profile.js
│   │   ├── reports.js
│   │   └── transactions.js
│   │
│   └── index.php                   # Application entry point
│
├── 📁 routes/                       # Routing configuration
│
├── 📁 vendor/                       # Composer dependencies
│   └── autoload.php
│
├── .gitignore                       # Git ignore rules
├── .htaccess                        # Apache rewrite rules
├── CHANGELOG.md                     # Version history
├── composer.json                    # Composer configuration
├── README.md                        # Project documentation
├── setup.bat                        # Windows setup script
└── setup.sh                         # Linux/Mac setup script
```

---

## 🔑 Thành Phần Chính

### 1️⃣ **Controllers** (`app/controllers/`)
Xử lý logic nghiệp vụ, nhận request từ user, gọi Models, trả về Views.

**Mỗi controller có**:
- `index()` - Hiển thị trang chính
- `api_*()` - API endpoints (AJAX)
- Validation & security checks

### 2️⃣ **Models** (`app/models/`)
Tương tác với database, thực hiện CRUD operations.

**Phương thức chuẩn**:
- `getById()`, `getAll()`, `getByUserId()`
- `create()`, `update()`, `delete()`
- Custom queries cho business logic

### 3️⃣ **Views** (`app/views/`)
Template hiển thị HTML, nhận dữ liệu từ Controllers.

**Cấu trúc**:
- `partials/header.php` - Header chung
- `partials/footer.php` - Footer chung
- `{module}/index.php` - View chính của module

### 4️⃣ **Services** (`app/services/`)
Business logic có thể tái sử dụng.

- **FinancialUtils**: Tính toán tài chính
- **Validator**: Validation & sanitization

### 5️⃣ **Middleware** (`app/middleware/`)
Xử lý request trước khi đến Controller.

- **CsrfProtection**: Bảo vệ CSRF attacks

---

## 🎨 Frontend Structure

### CSS Organization
```
style.css           # Base styles, layout, common components
{page}.css          # Page-specific styles
```

### JavaScript Organization
```
app.js              # Shared utilities (SmartSpending object)
input-masking.js    # Amount formatting
{page}.js           # Page-specific logic với AJAX
```

---

## 🗄️ Database Structure

### Core Tables
- **users** - Thông tin người dùng
- **categories** - Danh mục thu/chi
- **transactions** - Giao dịch
- **recurring_transactions** - Giao dịch định kỳ
- **budgets** - Ngân sách
- **goals** - Mục tiêu tiết kiệm
- **goal_transactions** - Liên kết goals & transactions

---

## 🚀 Flow Hoạt Động

### 1. Request Flow
```
User Request
    ↓
public/index.php (Entry point)
    ↓
app/core/App.php (Router)
    ↓
app/controllers/{Controller}.php
    ↓
app/models/{Model}.php (if needed)
    ↓
app/views/{view}.php
    ↓
Response to User
```

### 2. AJAX Flow
```
User Action (Click/Submit)
    ↓
JavaScript (fetch API)
    ↓
Controller::api_method()
    ↓
Model (CRUD)
    ↓
JSON Response
    ↓
JavaScript updates DOM
```

---

## 📝 Naming Conventions

### Files
- **Controllers**: PascalCase (e.g., `Transactions.php`)
- **Models**: PascalCase (e.g., `Transaction.php`)
- **Views**: lowercase (e.g., `index.php`)
- **CSS/JS**: kebab-case (e.g., `input-masking.js`)

### Code
- **Classes**: PascalCase (`class Transaction`)
- **Methods**: camelCase (`getById()`)
- **Variables**: camelCase (`$userId`)
- **Constants**: UPPER_SNAKE_CASE (`BASE_URL`)

---

## 🔒 Security Layers

1. **CSRF Protection** - Token validation trên POST requests
2. **Input Validation** - Validator service
3. **SQL Injection Prevention** - PDO prepared statements
4. **XSS Prevention** - Output escaping trong views
5. **Authentication** - Session-based user check

---

## 📦 Dependencies

### Composer Packages
```json
{
    "require": {
        "php": ">=7.4"
    }
}
```

### Frontend Libraries
- Bootstrap 5.1.3 - UI framework
- Chart.js - Data visualization
- Font Awesome 6.0 - Icons
- Bootstrap Icons - Additional icons

---

## 🧪 Development Workflow

### 1. Adding New Feature
1. Create migration SQL (if needed)
2. Create/Update Model
3. Create/Update Controller
4. Create/Update View
5. Add JavaScript (if AJAX)
6. Add CSS styling
7. Update documentation

### 2. API Development
1. Add `api_*()` method in Controller
2. Add validation rules
3. Add CSRF check
4. Return `ApiResponse::success()` or `::error()`

---

## 📚 Key Files to Know

| File | Purpose |
|------|---------|
| `public/index.php` | Application entry point |
| `app/core/App.php` | Router & dispatcher |
| `config/database.php` | DB credentials |
| `app/core/ApiResponse.php` | JSON response helper |
| `app/services/Validator.php` | Input validation |
| `app/middleware/CsrfProtection.php` | CSRF protection |

---

## 🎯 Best Practices

1. ✅ **Always validate input** - Use Validator service
2. ✅ **Use prepared statements** - Prevent SQL injection
3. ✅ **Escape output** - Use `$this->escape()` in views
4. ✅ **Check authentication** - Verify `$_SESSION['user_id']`
5. ✅ **Use CSRF tokens** - On all POST requests
6. ✅ **Follow MVC pattern** - Không mix logic vào views
7. ✅ **Comment your code** - PHPDoc cho functions
8. ✅ **Handle errors** - Try-catch blocks

---

## 📖 Documentation Location

- **API Docs**: `docs/API.md`
- **Migration Guide**: `docs/MIGRATION_GUIDE.md`
- **Quick Start**: `docs/guides/QUICK_START.md`
- **Goals Guide**: `docs/guides/GOALS_INSTALLATION_GUIDE.md`
- **Completion Summary**: `docs/guides/FINAL_COMPLETION_SUMMARY.md`

---

## 🔄 Version Control

```bash
# Current version: 2.0.0
# See CHANGELOG.md for version history
```

---

**Last Updated**: December 1, 2025  
**Maintainer**: SmartSpending Team
