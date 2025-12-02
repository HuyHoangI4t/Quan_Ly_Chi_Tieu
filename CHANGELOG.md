# Changelog

All notable changes to SmartSpending project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2025-12-01

### 🎉 Major Release - Complete Feature Set

### Added

#### 🏗️ Architecture & Code Quality
- **Service Layer**: 
  - `FinancialUtils` service for financial calculations (SRP compliance)
  - `Validator` service for comprehensive input validation
- **API Response Standardization**: 
  - `ApiResponse` helper class
  - Uniform JSON format: `{success, message, data}` for all endpoints
- **PSR-4 Autoloading**: Enhanced Composer configuration
- **Separation of Concerns**: Logic moved from models to services

#### 🔒 Security Enhancements
- **CSRF Protection Middleware**: 
  - Token-based CSRF protection for all POST requests
  - Support for both forms and AJAX requests
  - Token expiration after 1 hour
  - Auto-regeneration on login/logout
- **Data Validation & Sanitization**:
  - Comprehensive input validation for all user inputs
  - XSS prevention with `htmlspecialchars` and `strip_tags`
  - SQL injection prevention with PDO prepared statements
  - Email validation with `filter_var`
  - Password strength requirements (min 6 characters)
- **Type Safety**: Strict type checking in validators

#### 💰 Core Features
- **Budgets System**:
  - Full CRUD operations for budgets
  - Real-time spending calculation
  - Budget status indicators (Safe/Warning/Exceeded)
  - Visual progress bars with color coding
  - Monthly budget summaries
  - Budget vs actual comparison
  - Category-wise budget allocation
  - Period-based filtering (YYYY-MM format)
  
- **Recurring Transactions**:
  - Create recurring transactions (daily, weekly, monthly, yearly)
  - Automatic generation of actual transactions
  - Start date and optional end date
  - Pause/Resume functionality
  - Last generation tracking
  - Batch processing support
  
- **Custom Categories**:
  - User-specific custom categories
  - Separate from default categories
  - Full CRUD operations
  - Cannot delete categories in use
  - Type-based filtering (income/expense)

#### 📊 Database
- **New Tables**:
  - `recurring_transactions`: Store recurring transaction templates
  - `budgets`: Track budget limits and spending
- **Schema Improvements**:
  - Added indexes for better query performance
  - Foreign key constraints for data integrity
  - Composite unique constraint on budgets
- **View**:
  - `budget_progress`: Calculated view for budget tracking
- **Migrations**:
  - `001_add_recurring_and_budgets.sql`: Complete migration script

#### 📖 Documentation
- **API Documentation** (`docs/API.md`):
  - Complete API reference
  - Request/Response examples
  - Error handling guide
  - Authentication requirements
  - CSRF token usage
  - JavaScript usage examples
  
- **README** (`README_NEW.md`):
  - Project overview and features
  - Architecture explanation (Custom MVC)
  - Installation guide (step-by-step)
  - Configuration instructions
  - Usage guidelines
  - Screenshots placeholders
  - Contribution guidelines
  
- **Migration Guide** (`docs/MIGRATION_GUIDE.md`):
  - Database migration instructions
  - Rollback procedures
  - Troubleshooting guide
  - Testing checklist

#### 🎨 UI/UX Improvements
- **Budget Management UI**:
  - Summary cards with key metrics
  - Interactive budget table
  - Modal forms for add/edit
  - Color-coded progress indicators
  - Period selector dropdown
  
- **Enhanced Forms**:
  - Better validation messages
  - Loading states on submit buttons
  - Toast notifications
  - Responsive design improvements

### Changed

#### Refactored
- **Transaction Model**:
  - Removed calculation logic → moved to `FinancialUtils`
  - Removed date logic → moved to `FinancialUtils`
  - Cleaner, more focused code
  
- **Controllers**:
  - Standardized API responses using `ApiResponse`
  - Integrated CSRF verification
  - Integrated Validator service
  - Improved error handling
  - Removed duplicate code
  
- **Category Model**:
  - Added support for user-specific categories
  - Enhanced query methods with user context
  - Added existence check methods

#### Improved
- **Database Queries**:
  - Added indexes for faster queries
  - Optimized JOIN operations
  - Better use of aggregation functions
  
- **Error Handling**:
  - Consistent error responses
  - Detailed validation errors
  - HTTP status codes alignment
  - User-friendly error messages in Vietnamese

### Fixed
- **Security Vulnerabilities**:
  - XSS prevention in all user inputs
  - SQL injection prevention
  - CSRF attack prevention
  
- **Data Integrity**:
  - Foreign key constraints prevent orphaned records
  - Unique constraints prevent duplicate budgets
  - Validation prevents invalid data entry

### Security
- All POST endpoints now require CSRF token
- All user inputs are validated and sanitized
- Passwords are hashed with bcrypt
- Session management improvements
- SQL injection prevention with PDO prepared statements

---

## [1.0.0] - 2025-11-24

### Initial Release

#### Features
- User authentication (login/register)
- Transaction management (add/edit/delete)
- Dashboard with charts (Line Chart, Pie Chart)
- Category-based filtering
- Date range filtering
- Profile management
- Data export to CSV
- Reports generation
- Custom MVC architecture
- Bootstrap 5 UI
- Chart.js integration

#### Database
- Users table
- Categories table (default categories)
- Transactions table
- Goals table (placeholder)

#### Documentation
- Basic README
- Database schema SQL file
- Sample data SQL file

#### 🎯 Goals Tracking System (NEW)
- **Full CRUD Operations**: Create, read, update, delete goals
- **Goal-Transaction Linking**: Track which transactions contribute to goals
- **Progress Tracking**: Real-time calculation of saved vs target amount
- **Status Management**: Active/Completed/Cancelled statuses
- **Deadline Countdown**: Visual countdown to goal deadline
- **Statistics Dashboard**: Total goals, active goals, completed goals, completion rate
- **Modern UI**: Bootstrap 5 cards with progress bars and icons
- **AJAX Integration**: No page reloads for all operations
- **Validation**: Amount validation, date validation, required fields

#### 📊 Enhanced Reports
- **Dynamic Filters**: Filter by period (month, quarter, year) and transaction type
- **AJAX Updates**: Charts update without page reload
- **Multiple Chart Types**: Line chart (trends), Pie chart (category breakdown)
- **Export Functionality**: Export filtered reports to CSV

### Changed
- **Database Schema**: Complete rewrite with views, stored procedures, and triggers
  - `schema.sql`: New comprehensive schema with detailed comments
  - `sample_data.sql`: Updated sample data for December 2025
  - Added 2 views: `v_monthly_summary`, `v_category_summary`
  - Added 2 procedures: `sp_get_user_balance`, `sp_get_budget_status`
  - Added trigger: `trg_transactions_set_type` (auto-set type from category)
  
- **File Structure Optimization**:
  - Moved documentation to `docs/guides/`
  - Renamed `README_NEW.md` → `README.md`
  - Renamed `goals_new.css` → `goals.css`
  - Created comprehensive `.gitignore`
  - Added `PROJECT_STRUCTURE.md` (450 lines)
  - Added `CONTRIBUTING.md` (400 lines)

### Removed
- **Dark Mode Feature**: Removed to simplify UI (~450 lines)
  - Deleted `public/css/dark-mode.css`
  - Deleted `public/js/theme-manager.js`
  - Removed dark mode toggle from all views
  
- **Floating Action Button**: Removed from footer to reduce UI clutter

- **Unnecessary Documentation**:
  - Deleted `FINAL_COMPLETION_SUMMARY.md`
  - Deleted `UPGRADE_SUMMARY.md`
  - Deleted `OPTIMIZATION_SUMMARY.md`
  - Kept only essential docs (7 files)

- **Old SQL Files**:
  - Deleted `quan_ly_chi_tieu.sql` (replaced by `schema.sql`)
  - Deleted `sample_data_october_2025.sql` (replaced by `sample_data.sql`)

---

## [Unreleased]

### Planned Features
- [ ] Multi-currency support
- [ ] Email notifications for budget alerts
- [ ] Two-factor authentication
- [ ] Mobile app (React Native)
- [ ] Bank account integration
- [ ] Receipt scanning with OCR
- [ ] Collaborative budgets (family sharing)
- [ ] Investment tracking
- [ ] PWA support

---

## Notes

### Breaking Changes in 2.0.0
- Database schema completely rewritten - use `schema.sql` for new installations
- Dark mode removed - old theme settings will be ignored
- Floating action button removed from UI
- API responses format changed (now includes `data` field)
- All POST endpoints require CSRF token
- Category model methods now accept `$userId` parameter

### Installation for 2.0.0
For new installations, use `database/schema.sql` instead of old SQL files.
See `database/README.md` for migration instructions.

### Deprecations
- Direct SQL queries without PDO - will be removed in 3.0.0
- Unvalidated user inputs - all inputs must use Validator service

---

## Contributors
- **HUYHOANG** - Initial work and major upgrades

---

## Support
For issues and questions:
- GitHub Issues: https://github.com/HuyHoangI4t/Quan_Ly_Chi_Tieu/issues
- Email: huyhoangpro187@gmail.com
