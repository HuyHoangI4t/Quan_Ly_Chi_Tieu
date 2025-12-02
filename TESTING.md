# Testing Guide - Quan Ly Chi Tieu

## Test Environment Setup

### Server
```bash
php -S localhost:8000 -t public
```

Access: http://localhost:8000

### Test Accounts

#### Admin Account
- **Username:** admin
- **Email:** admin@test.com
- **Password:** password
- **Role:** admin

#### Regular User Account
- **Username:** user1
- **Email:** user1@test.com
- **Password:** password
- **Role:** user

---

## Test Cases

### 1. Authentication Testing ✓

#### Test 1.1: Login Page
- [ ] Access http://localhost:8000
- [ ] Verify login form displays correctly
- [ ] Check CSS loads properly (login_signup.css)

#### Test 1.2: User Login
- [ ] Login with user1@test.com / password
- [ ] Should redirect to /dashboard
- [ ] Verify user dashboard loads

#### Test 1.3: Admin Login
- [ ] Logout from user account
- [ ] Login with admin@test.com / password
- [ ] Should redirect to /admin/dashboard (if admin routes configured)
- [ ] Verify admin dashboard loads

---

### 2. User Module Routes Testing ✓

Test all user module routes after login as user1:

#### Test 2.1: Dashboard
- [ ] Access /dashboard
- [ ] Verify page loads with correct CSS (/public/user/dashboard/dashboard.css)
- [ ] Check JS loads (/public/user/dashboard/dashboard.js)
- [ ] Verify header navigation works

#### Test 2.2: Transactions
- [ ] Access /transactions
- [ ] Verify CSS loads (/public/user/transactions/transactions.css)
- [ ] Check JS loads (/public/user/transactions/transactions.js)

#### Test 2.3: Budgets
- [ ] Access /budgets
- [ ] Verify CSS loads (/public/user/budgets/budgets.css)
- [ ] Check JS loads (/public/user/budgets/budgets.js)

#### Test 2.4: Goals
- [ ] Access /goals
- [ ] Verify CSS loads (/public/user/goals/goals.css)
- [ ] Check JS loads (/public/user/goals/goals.js)

#### Test 2.5: Reports
- [ ] Access /reports
- [ ] Verify CSS loads (/public/user/reports/reports.css)
- [ ] Check JS loads (/public/user/reports/reports.js)

#### Test 2.6: Profile
- [ ] Access /profile
- [ ] Verify CSS loads (/public/user/profile/profile.css)
- [ ] Check JS loads (/public/user/profile/profile.js)

---

### 3. Admin Module Routes Testing ✓

Test admin routes after login as admin:

#### Test 3.1: Admin Dashboard
- [ ] Access /admin/dashboard or /admin
- [ ] Verify admin dashboard loads
- [ ] Check CSS loads (/public/admin/dashboard.css)
- [ ] Check JS loads (/public/admin/dashboard.js)
- [ ] Verify system statistics display:
  - Total users count
  - Active users count
  - Total transactions
  - Total categories
  - Recent users list
  - System activity

#### Test 3.2: Admin Users Management
- [ ] Access /admin/users
- [ ] Verify users list displays
- [ ] Test user management functions

---

### 4. Authorization Testing ✓

#### Test 4.1: User Access Control
- [ ] Login as regular user (user1)
- [ ] Try to access /admin/dashboard
- [ ] Should be blocked or redirected
- [ ] Verify error message or redirect

#### Test 4.2: Admin Access
- [ ] Login as admin
- [ ] Access /admin/dashboard
- [ ] Should have full access
- [ ] Try accessing user routes (/dashboard, /transactions)
- [ ] Should work normally

---

### 5. Asset Loading Testing ✓

#### Test 5.1: Shared Assets
- [ ] Verify /public/shared/style.css loads on all pages
- [ ] Check Bootstrap CSS loads
- [ ] Check Font Awesome icons display

#### Test 5.2: Module-Specific Assets
- [ ] User pages load from /public/user/[page]/
- [ ] Admin pages load from /public/admin/
- [ ] Login page loads from /public/login_signup/

#### Test 5.3: Browser Console
- [ ] Open browser console (F12)
- [ ] Navigate through pages
- [ ] Check for 404 errors on CSS/JS files
- [ ] Verify no JavaScript errors

---

### 6. Navigation Testing ✓

#### Test 6.1: Header Navigation
- [ ] Test all navigation links in header
- [ ] Verify active link highlighting
- [ ] Check mobile responsive menu (if applicable)

#### Test 6.2: Logout
- [ ] Click logout button
- [ ] Should redirect to login page
- [ ] Session should be cleared
- [ ] Cannot access protected pages after logout

---

### 7. Database Integration Testing ✓

#### Test 7.1: Admin Dashboard Statistics
- [ ] Login as admin
- [ ] View dashboard statistics
- [ ] Verify counts match database:
  ```sql
  SELECT COUNT(*) FROM users;
  SELECT COUNT(*) FROM users WHERE is_active = 1;
  SELECT COUNT(*) FROM transactions;
  SELECT COUNT(*) FROM categories;
  ```

#### Test 7.2: Recent Users
- [ ] Check recent users list on admin dashboard
- [ ] Should display latest registered users
- [ ] Verify user details are correct

---

## Browser Compatibility Testing

Test on multiple browsers:
- [ ] Chrome/Edge
- [ ] Firefox
- [ ] Safari (if available)

---

## Performance Testing

- [ ] Check page load times
- [ ] Verify no N+1 query issues
- [ ] Test with multiple concurrent users

---

## Known Issues

Document any bugs found during testing:

### Issue 1: [Title]
- **Description:** 
- **Steps to Reproduce:**
- **Expected Behavior:**
- **Actual Behavior:**
- **Status:** Open/Fixed

---

## Test Results Summary

| Category | Pass | Fail | Notes |
|----------|------|------|-------|
| Authentication | 0/3 | 0/3 | |
| User Routes | 0/6 | 0/6 | |
| Admin Routes | 0/2 | 0/2 | |
| Authorization | 0/2 | 0/2 | |
| Assets Loading | 0/3 | 0/3 | |
| Navigation | 0/2 | 0/2 | |
| Database | 0/2 | 0/2 | |
| **Total** | **0/20** | **0/20** | |

---

## Notes

- Password for all test accounts: **password**
- Database: quan_ly_chi_tieu
- Server: localhost:8000
- Base URL dynamically determined from script path
