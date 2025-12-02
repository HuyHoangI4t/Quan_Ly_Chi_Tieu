# 🚀 Quick Start Guide - Quan Ly Chi Tieu

## ⚡ Fast Setup (5 minutes)

### 1️⃣ Verify XAMPP MySQL is Running
```powershell
# Check MySQL service
Get-Service | Where-Object {$_.Name -like "*mysql*"}

# If not running, start XAMPP
cd c:\xampp
.\xampp-control.exe
```

### 2️⃣ Verify Database Exists
```powershell
cd c:\xampp
.\mysql\bin\mysql.exe -u root -e "SHOW DATABASES LIKE 'quan_ly_chi_tieu';"
```

**Expected output:**
```
+-----------------------------+
| Database (quan_ly_chi_tieu) |
+-----------------------------+
| quan_ly_chi_tieu            |
+-----------------------------+
```

### 3️⃣ Start PHP Development Server
```powershell
cd c:\xampp\htdocs\Quan_Ly_Chi_Tieu
php -S localhost:8000 -t public
```

**Expected output:**
```
PHP 8.0.30 Development Server (http://localhost:8000) started
```

### 4️⃣ Open Browser
```
http://localhost:8000
```

---

## 🔐 Login Credentials

### Admin Account
- **Email:** admin@test.com
- **Password:** password
- **Access:** /admin/dashboard

### User Account
- **Email:** user1@test.com
- **Password:** password
- **Access:** /dashboard

---

## 📍 Available Routes

### 🔵 User Routes (after login as user1)
```
http://localhost:8000/dashboard       # Dashboard overview
http://localhost:8000/transactions    # Transaction management
http://localhost:8000/budgets         # Budget planning
http://localhost:8000/goals           # Financial goals
http://localhost:8000/reports         # Reports & analytics
http://localhost:8000/profile         # User profile
```

### 🔴 Admin Routes (after login as admin)
```
http://localhost:8000/admin/dashboard # Admin dashboard
http://localhost:8000/admin/users     # User management
```

---

## ✅ Verification Checklist

### Check Server
- [ ] PHP server running on port 8000
- [ ] No error messages in terminal
- [ ] Can access http://localhost:8000

### Check Database
```powershell
# Verify users
cd c:\xampp
.\mysql\bin\mysql.exe -u root quan_ly_chi_tieu -e "SELECT id, username, email, role FROM users;"
```

**Expected:** 2 users (admin, user1)

### Check Login
- [ ] Login page loads without errors
- [ ] CSS loads correctly (no 404 in browser console F12)
- [ ] Can login with test accounts
- [ ] Admin redirects to /admin/dashboard
- [ ] User redirects to /dashboard

---

## 🐛 Troubleshooting

### Problem: Can't access http://localhost:8000
**Solution:**
```powershell
# Check if port 8000 is already in use
netstat -ano | findstr :8000

# If occupied, use different port
php -S localhost:8001 -t public
```

### Problem: Database connection error
**Solution:**
```powershell
# Start XAMPP MySQL
cd c:\xampp
.\xampp-control.exe

# Or start MySQL service directly
net start mysql
```

### Problem: 404 errors on CSS/JS files
**Solution:**
- Verify public/ directory structure exists
- Check browser console (F12) for exact 404 paths
- All asset paths should NOT have /public/ prefix

### Problem: Login fails
**Solution:**
```powershell
# Verify users exist
cd c:\xampp
.\mysql\bin\mysql.exe -u root quan_ly_chi_tieu -e "SELECT * FROM users;"

# If no users, recreate them
.\mysql\bin\mysql.exe -u root quan_ly_chi_tieu -e "INSERT INTO users (username, email, password, full_name, role, is_active) VALUES ('admin', 'admin@test.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin', 1), ('user1', 'user1@test.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test User', 'user', 1);"
```

### Problem: Access denied to /admin
**Solution:**
- Must login as admin (admin@test.com)
- Regular users cannot access /admin routes
- This is expected behavior (authorization working)

---

## 📱 Browser Console Check

Press **F12** to open Developer Tools, check Console tab:

### ✅ Good (No errors)
```
No errors
All assets loaded successfully
```

### ❌ Bad (Has errors)
```
GET http://localhost:8000/public/dashboard/dashboard.css 404 (Not Found)
```

**Fix:** Asset path still has /public/ prefix, need to update view file

---

## 🎯 Quick Test Scenario

### Test 1: User Login & Navigation (2 minutes)
1. Open http://localhost:8000
2. Login: user1@test.com / password
3. Should see dashboard with welcome message
4. Click "Giao dịch" → Should see transactions page
5. Click "Ngân sách" → Should see budgets page
6. All pages should load without errors

### Test 2: Admin Access (2 minutes)
1. Logout (top right)
2. Login: admin@test.com / password
3. Should see admin dashboard with statistics
4. Should see: Total users: 2, Active users: 2
5. Should see recent users list
6. Try /admin/users → Should see user management page

### Test 3: Authorization (1 minute)
1. Logout
2. Login: user1@test.com / password
3. Manually navigate to http://localhost:8000/admin/dashboard
4. Should see "Access Denied: Admin only"
5. This is correct behavior ✅

---

## 📊 Database Quick View

### View all data:
```powershell
cd c:\xampp

# Users
.\mysql\bin\mysql.exe -u root quan_ly_chi_tieu -e "SELECT * FROM users;"

# Categories
.\mysql\bin\mysql.exe -u root quan_ly_chi_tieu -e "SELECT id, name, type FROM categories LIMIT 10;"

# Transactions
.\mysql\bin\mysql.exe -u root quan_ly_chi_tieu -e "SELECT * FROM transactions;"
```

---

## 📂 Project Structure Quick Reference

```
Quan_Ly_Chi_Tieu/
├── app/
│   ├── controllers/
│   │   ├── User/          # User module controllers
│   │   ├── Admin/         # Admin module controllers
│   │   └── Login_signup.php
│   ├── views/
│   │   ├── user/          # User module views
│   │   ├── admin/         # Admin module views
│   │   └── partials/
│   └── models/
├── public/
│   ├── user/              # User assets
│   ├── admin/             # Admin assets
│   ├── shared/            # Common assets
│   └── index.php          # Entry point
└── config/
    └── database.php       # DB config
```

---

## 🔥 Commands Reference

### Start Server
```powershell
cd c:\xampp\htdocs\Quan_Ly_Chi_Tieu
php -S localhost:8000 -t public
```

### Stop Server
Press **Ctrl+C** in terminal

### Check MySQL
```powershell
cd c:\xampp
.\mysql\bin\mysql.exe -u root quan_ly_chi_tieu -e "SHOW TABLES;"
```

### Composer Update
```powershell
cd c:\xampp\htdocs\Quan_Ly_Chi_Tieu
composer dump-autoload
```

---

## 📖 Documentation

- 📘 **TESTING.md** - Complete testing guide
- 📙 **TEST_RESULTS.md** - Test execution report
- 📗 **COMPLETION_REPORT.md** - Full restructure documentation
- 📕 **RESTRUCTURE.md** - Module architecture details
- 📓 **README.md** - Project overview

---

## 🎉 Success Indicators

You know everything is working when:
- ✅ Login page loads with styled form
- ✅ No 404 errors in browser console
- ✅ User can access /dashboard, /transactions, etc.
- ✅ Admin can access /admin/dashboard
- ✅ Admin dashboard shows correct statistics
- ✅ User cannot access /admin (gets 403)
- ✅ Navigation works smoothly

---

## 💡 Tips

1. **Always check browser console (F12)** for errors
2. **Use Ctrl+Shift+R** to hard refresh and clear cache
3. **Check terminal** for PHP errors
4. **Verify MySQL is running** before starting
5. **Use correct port** if 8000 is occupied

---

## 📞 Need Help?

Check these files:
1. **TESTING.md** - Detailed test cases
2. **TROUBLESHOOTING section above**
3. **Browser console** - F12 for errors
4. **PHP terminal** - Check for error messages

---

**Ready to go! 🚀**

Start with: `php -S localhost:8000 -t public`
Then open: http://localhost:8000
