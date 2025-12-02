# 🤝 Đóng Góp Vào SmartSpending

Cảm ơn bạn quan tâm đến việc đóng góp vào SmartSpending! Hướng dẫn này sẽ giúp bạn bắt đầu.

---

## 📋 Mục Lục
- [Code of Conduct](#code-of-conduct)
- [Cách Đóng Góp](#cách-đóng-góp)
- [Development Setup](#development-setup)
- [Coding Standards](#coding-standards)
- [Commit Guidelines](#commit-guidelines)
- [Pull Request Process](#pull-request-process)

---

## 📜 Code of Conduct

- Tôn trọng mọi người tham gia dự án
- Sử dụng ngôn ngữ chuyên nghiệp và thân thiện
- Chấp nhận phản hồi mang tính xây dựng
- Tập trung vào điều tốt nhất cho cộng đồng

---

## 🚀 Cách Đóng Góp

### 1. Report Bugs 🐛
Nếu bạn tìm thấy lỗi, hãy tạo issue với:
- **Tiêu đề rõ ràng** mô tả vấn đề
- **Các bước tái hiện** lỗi
- **Kết quả mong đợi** vs **Kết quả thực tế**
- **Screenshots** (nếu có)
- **Môi trường**: PHP version, OS, Browser

### 2. Suggest Features ✨
Đề xuất tính năng mới:
- Mô tả chi tiết tính năng
- Giải thích tại sao cần tính năng này
- Đưa ra ví dụ sử dụng
- Nêu giải pháp thay thế (nếu có)

### 3. Submit Code 💻
Xem [Pull Request Process](#pull-request-process)

---

## 🛠️ Development Setup

### 1. Fork Repository
```bash
# Fork trên GitHub, sau đó clone
git clone https://github.com/YOUR_USERNAME/Quan_Ly_Chi_Tieu.git
cd Quan_Ly_Chi_Tieu
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Setup Database
```bash
# Import database schema
mysql -u root -p quan_ly_chi_tieu < database/quan_ly_chi_tieu.sql

# Run migrations
mysql -u root -p quan_ly_chi_tieu < database/migrations/001_add_recurring_and_budgets.sql
mysql -u root -p quan_ly_chi_tieu < database/migrations/002_add_goals_table.sql
```

### 4. Configure
```bash
# Copy và chỉnh sửa config/database.php
cp config/database.php.example config/database.php
```

### 5. Start Development Server
```bash
# Sử dụng XAMPP/WAMP hoặc PHP built-in server
php -S localhost:8000 -t public/
```

---

## 📐 Coding Standards

### PHP Standards
Tuân thủ **PSR-12** coding standard:

```php
<?php

namespace App\Controllers;

/**
 * Controller description
 */
class ExampleController extends Controllers
{
    /**
     * Method description
     * 
     * @param int $id
     * @return void
     */
    public function index($id = null)
    {
        // Code here
    }
}
```

### Naming Conventions
- **Classes**: `PascalCase`
- **Methods**: `camelCase`
- **Variables**: `$camelCase`
- **Constants**: `UPPER_SNAKE_CASE`
- **Files**: Match class name

### Code Style
```php
// ✅ Good
if ($condition) {
    doSomething();
}

// ❌ Bad
if($condition){
    doSomething();
}
```

### Database Queries
```php
// ✅ Good - Prepared statements
$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();

// ❌ Bad - SQL injection risk
$result = $db->query("SELECT * FROM users WHERE id = $id");
```

### Security Practices
```php
// ✅ Always validate input
$validator = new Validator();
$rules = ['email' => ['required' => true, 'email' => true]];
if (!$validator->validate($_POST, $rules)) {
    // Handle errors
}

// ✅ Use CSRF protection
if (!$this->csrfProtection->validateToken($_POST['csrf_token'])) {
    // Reject request
}

// ✅ Escape output
echo $this->escape($userInput);
```

### JavaScript Style
```javascript
// ✅ Use modern ES6+
const fetchData = async () => {
    try {
        const response = await fetch(url);
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error:', error);
    }
};

// ✅ Use meaningful names
const calculateTotalAmount = (transactions) => {
    return transactions.reduce((sum, t) => sum + t.amount, 0);
};
```

### CSS Organization
```css
/* ✅ Use BEM naming */
.goal-card { }
.goal-card__header { }
.goal-card__title { }
.goal-card--active { }

/* ✅ Group related properties */
.card {
    /* Positioning */
    position: relative;
    
    /* Box model */
    display: flex;
    padding: 1rem;
    
    /* Visual */
    background: white;
    border-radius: 8px;
    
    /* Typography */
    font-size: 1rem;
    
    /* Animation */
    transition: all 0.3s ease;
}
```

---

## 📝 Commit Guidelines

### Commit Message Format
```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types
- `feat`: Tính năng mới
- `fix`: Sửa lỗi
- `docs`: Cập nhật documentation
- `style`: Format code (không thay đổi logic)
- `refactor`: Refactor code
- `test`: Thêm/sửa tests
- `chore`: Maintenance tasks

### Examples
```bash
# Good commits
git commit -m "feat(goals): add goal progress tracking"
git commit -m "fix(transactions): resolve amount formatting issue"
git commit -m "docs(readme): update installation steps"

# Bad commits
git commit -m "update"
git commit -m "fix bug"
git commit -m "changes"
```

### Detailed Commit
```bash
git commit -m "feat(budgets): implement budget alert notifications

- Add email notification service
- Create budget alert template
- Integrate with cron job
- Add user notification preferences

Closes #123"
```

---

## 🔀 Pull Request Process

### 1. Create Branch
```bash
# Create feature branch from main
git checkout -b feature/add-budget-alerts

# Or bugfix branch
git checkout -b fix/transaction-date-bug
```

### 2. Make Changes
- Follow coding standards
- Write clear, self-documenting code
- Add comments for complex logic
- Update documentation if needed

### 3. Test Changes
```bash
# Manual testing
# - Test all affected features
# - Test edge cases
# - Test on different browsers
# - Check responsive design

# Check for errors
# - No PHP errors
# - No JavaScript console errors
# - No SQL errors
```

### 4. Commit Changes
```bash
git add .
git commit -m "feat(budgets): add budget alert notifications"
```

### 5. Push to Fork
```bash
git push origin feature/add-budget-alerts
```

### 6. Create Pull Request
Trên GitHub, create pull request với:

**Title**: Clear, descriptive title
```
feat(budgets): Add budget alert notifications
```

**Description**: Detailed explanation
```markdown
## Changes
- Added email notification service
- Created budget alert template
- Integrated with cron job

## Testing
- [x] Manual testing completed
- [x] No errors in console
- [x] Works on Chrome, Firefox, Safari
- [x] Responsive on mobile

## Screenshots
[Add screenshots if UI changes]

## Related Issues
Closes #123
```

### 7. Review Process
- Maintainers sẽ review code
- Thực hiện requested changes nếu có
- Sau khi approved, PR sẽ được merge

---

## ✅ Pull Request Checklist

Trước khi submit PR, đảm bảo:

- [ ] Code tuân thủ coding standards
- [ ] Không có hardcoded credentials
- [ ] Đã test thủ công
- [ ] Documentation đã cập nhật
- [ ] Commit messages rõ ràng
- [ ] No console errors
- [ ] No PHP warnings/errors
- [ ] Responsive design works
- [ ] CSRF protection implemented (for forms)
- [ ] Input validation added
- [ ] SQL queries use prepared statements

---

## 📚 Resources

### Documentation
- [Project Structure](PROJECT_STRUCTURE.md)
- [API Documentation](docs/API.md)
- [Quick Start Guide](docs/guides/QUICK_START.md)

### Learning
- [PHP The Right Way](https://phptherightway.com/)
- [PSR-12 Standard](https://www.php-fig.org/psr/psr-12/)
- [MVC Pattern](https://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93controller)

---

## 🆘 Getting Help

### Questions?
- Check [existing issues](https://github.com/HuyHoangI4t/Quan_Ly_Chi_Tieu/issues)
- Read [documentation](docs/)
- Ask in [Discussions](https://github.com/HuyHoangI4t/Quan_Ly_Chi_Tieu/discussions)

### Found a Security Issue?
**DO NOT** create public issue. Email: security@smartspending.com

---

## 🎉 Recognition

Contributors sẽ được:
- Listed trong README.md
- Mentioned trong release notes
- Our eternal gratitude! 🙏

---

## 📄 License

Bằng việc đóng góp, bạn đồng ý rằng contributions của bạn sẽ được licensed theo MIT License của dự án.

---

**Thank you for contributing to SmartSpending!** 💚
