-- ============================================
-- SmartSpending - Complete Database Setup
-- Version: 5.0.0
-- Date: December 5, 2025
-- Description: Setup đầy đủ theo thứ tự logic đúng
-- Author: GitHub Copilot
-- ============================================
-- 
-- THỨ TỰ THỰC HIỆN:
-- 1. Khởi tạo DB & Schema (DROP/CREATE TABLES)
-- 2. Bảng Migration (jar_templates, jar_categories, jar_allocations_v2)
-- 3. Khóa ngoại & Index (Foreign Keys, Indexes)
-- 4. Views & Procedures (Phụ thuộc vào tables)
-- 5. Dữ liệu Mặc định (Default Categories)
-- 6. Dữ liệu Test (Demo user & transactions)
-- ============================================

-- ============================================
-- BƯỚC 1: KHỞI TẠO DATABASE & DROP TABLES
-- ============================================

CREATE DATABASE IF NOT EXISTS `quan_ly_chi_tieu` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `quan_ly_chi_tieu`;

-- Set configuration
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";
SET FOREIGN_KEY_CHECKS = 0;

-- Drop all tables (reverse order to avoid FK conflicts)
DROP TABLE IF EXISTS `jar_allocations_v2`;
DROP TABLE IF EXISTS `jar_categories`;
DROP TABLE IF EXISTS `jar_templates`;
DROP TABLE IF EXISTS `goal_transactions`;
DROP TABLE IF EXISTS `goals`;
DROP TABLE IF EXISTS `recurring_transactions`;
DROP TABLE IF EXISTS `budgets`;
DROP TABLE IF EXISTS `jar_allocations`;
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

-- Drop views
DROP VIEW IF EXISTS `v_monthly_summary`;
DROP VIEW IF EXISTS `v_category_summary`;

-- Drop procedures
DROP PROCEDURE IF EXISTS `sp_get_user_balance`;
DROP PROCEDURE IF EXISTS `sp_get_budget_status`;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- BƯỚC 1.1: CREATE CORE TABLES
-- ============================================

-- Table: users
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE COMMENT 'Tên đăng nhập',
  `email` varchar(100) NOT NULL UNIQUE COMMENT 'Email người dùng',
  `password` varchar(255) NOT NULL COMMENT 'Mật khẩu đã mã hóa (bcrypt)',
  `full_name` varchar(100) DEFAULT NULL COMMENT 'Họ và tên',
  `role` enum('user','admin') NOT NULL DEFAULT 'user' COMMENT 'Vai trò: user hoặc admin',
  `is_super_admin` tinyint(1) DEFAULT 0 COMMENT 'Super admin không thể bị demote hoặc xóa',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Trạng thái tài khoản: 1=active, 0=disabled',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng người dùng';

-- Table: categories (NO FK yet - will be added in step 3)
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT 'NULL = danh mục mặc định, NOT NULL = danh mục tùy chỉnh',
  `name` varchar(100) NOT NULL COMMENT 'Tên danh mục',
  `type` enum('income','expense') NOT NULL COMMENT 'Loại: thu nhập hoặc chi tiêu',
  `color` varchar(7) DEFAULT '#3498db' COMMENT 'Mã màu hex',
  `icon` varchar(50) DEFAULT 'fa-circle' COMMENT 'Font Awesome icon class',
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = danh mục hệ thống, 0 = tùy chỉnh',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng danh mục';

-- Table: transactions (NO FK yet - will be added in step 3)
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'ID người dùng',
  `category_id` int(11) NOT NULL COMMENT 'ID danh mục',
  `amount` decimal(15,2) NOT NULL COMMENT 'Số tiền giao dịch',
  `type` enum('income','expense') NOT NULL COMMENT 'Loại: thu nhập hoặc chi tiêu',
  `description` text DEFAULT NULL COMMENT 'Mô tả giao dịch',
  `date` date NOT NULL COMMENT 'Ngày giao dịch',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_date` (`date`),
  KEY `idx_type` (`type`),
  KEY `idx_user_date` (`user_id`, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng giao dịch';

-- Table: goals (NO FK yet - will be added in step 3)
CREATE TABLE `goals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'ID người dùng',
  `name` varchar(100) NOT NULL COMMENT 'Tên mục tiêu',
  `description` text DEFAULT NULL COMMENT 'Mô tả chi tiết',
  `target_amount` decimal(15,2) NOT NULL COMMENT 'Số tiền mục tiêu',
  `current_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền hiện tại',
  `deadline` date DEFAULT NULL COMMENT 'Thời hạn hoàn thành',
  `status` enum('active','completed','paused','cancelled') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng mục tiêu tài chính';

-- Table: goal_transactions (NO FK yet - will be added in step 3)
CREATE TABLE `goal_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `goal_id` int(11) NOT NULL COMMENT 'ID mục tiêu',
  `transaction_id` int(11) NOT NULL COMMENT 'ID giao dịch',
  `amount` decimal(15,2) NOT NULL COMMENT 'Số tiền đóng góp',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_goal_id` (`goal_id`),
  KEY `idx_transaction_id` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Liên kết giao dịch với mục tiêu';

-- Table: budgets (NO FK yet - will be added in step 3)
CREATE TABLE `budgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'ID người dùng',
  `category_id` int(11) NOT NULL COMMENT 'ID danh mục',
  `amount` decimal(15,2) NOT NULL COMMENT 'Số tiền ngân sách',
  `period` enum('weekly','monthly','yearly') NOT NULL DEFAULT 'monthly',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `alert_threshold` decimal(5,2) DEFAULT 80.00 COMMENT 'Ngưỡng cảnh báo (%)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_period` (`period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng ngân sách';

-- Table: recurring_transactions (NO FK yet - will be added in step 3)
CREATE TABLE `recurring_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'ID người dùng',
  `category_id` int(11) NOT NULL COMMENT 'ID danh mục',
  `amount` decimal(15,2) NOT NULL COMMENT 'Số tiền',
  `type` enum('income','expense') NOT NULL COMMENT 'Loại giao dịch',
  `description` text DEFAULT NULL COMMENT 'Mô tả',
  `frequency` enum('daily','weekly','monthly','yearly') NOT NULL DEFAULT 'monthly',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `next_occurrence` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_next_occurrence` (`next_occurrence`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Giao dịch định kỳ';

-- Table: jar_allocations (Legacy - for backward compatibility)
CREATE TABLE `jar_allocations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'ID người dùng',
  `month` varchar(7) NOT NULL COMMENT 'Tháng (YYYY-MM)',
  `total_income` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Tổng thu nhập',
  `nec_percentage` decimal(5,2) NOT NULL DEFAULT 55.00 COMMENT 'Necessities %',
  `nec_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền Thiết yếu',
  `nec_spent` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Đã chi Thiết yếu',
  `ffa_percentage` decimal(5,2) NOT NULL DEFAULT 10.00 COMMENT 'FFA %',
  `ffa_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền FFA',
  `ffa_invested` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Đã đầu tư FFA',
  `edu_percentage` decimal(5,2) NOT NULL DEFAULT 10.00 COMMENT 'Education %',
  `edu_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền Giáo dục',
  `edu_spent` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Đã chi Giáo dục',
  `ltss_percentage` decimal(5,2) NOT NULL DEFAULT 10.00 COMMENT 'LTSS %',
  `ltss_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền LTSS',
  `ltss_saved` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Đã tiết kiệm LTSS',
  `play_percentage` decimal(5,2) NOT NULL DEFAULT 10.00 COMMENT 'Play %',
  `play_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền Vui chơi',
  `play_spent` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Đã chi Vui chơi',
  `give_percentage` decimal(5,2) NOT NULL DEFAULT 5.00 COMMENT 'Give %',
  `give_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền Từ thiện',
  `give_spent` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Đã chi Từ thiện',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_month` (`user_id`, `month`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_month` (`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng phân bổ 6 chiếc lọ (Legacy)';

-- ============================================
-- BƯỚC 2: MIGRATION - JAR TEMPLATES (50/30/20)
-- ============================================

-- Table: jar_templates (Custom jar system)
CREATE TABLE `jar_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'ID người dùng',
  `name` varchar(100) NOT NULL COMMENT 'Tên nhóm/mục đích (VD: Nhu cầu thiết yếu)',
  `percentage` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Phần trăm phân bổ (VD: 50.00)',
  `color` varchar(20) NOT NULL DEFAULT '#6c757d' COMMENT 'Màu hiển thị (hex)',
  `icon` varchar(50) DEFAULT NULL COMMENT 'Font Awesome icon class',
  `description` text DEFAULT NULL COMMENT 'Mô tả nhóm',
  `order_index` int(11) NOT NULL DEFAULT 0 COMMENT 'Thứ tự hiển thị',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Mẫu nhóm ngân sách linh hoạt (50/30/20)';

-- Table: jar_categories (Categories within each jar)
CREATE TABLE `jar_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `jar_id` int(11) NOT NULL COMMENT 'ID nhóm',
  `category_name` varchar(100) NOT NULL COMMENT 'Tên mục con (VD: Ăn uống, Đi lại)',
  `order_index` int(11) NOT NULL DEFAULT 0 COMMENT 'Thứ tự hiển thị',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_jar_id` (`jar_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Danh mục con trong mỗi nhóm ngân sách';

-- Table: jar_allocations_v2 (Dynamic allocations)
CREATE TABLE `jar_allocations_v2` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'ID người dùng',
  `jar_template_id` int(11) NOT NULL COMMENT 'ID mẫu nhóm',
  `month` varchar(7) NOT NULL COMMENT 'Tháng phân bổ (YYYY-MM)',
  `allocated_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền được phân bổ',
  `spent_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền đã chi/tiết kiệm',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_jar_month` (`user_id`, `jar_template_id`, `month`),
  KEY `idx_user_month` (`user_id`, `month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Phân bổ ngân sách linh hoạt';

-- ============================================
-- BƯỚC 3: FOREIGN KEYS & INDEXES
-- (Phải chạy SAU KHI tất cả tables đã tồn tại)
-- ============================================

-- Foreign Keys for categories
ALTER TABLE `categories`
ADD CONSTRAINT `fk_categories_user_id` 
FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) 
ON DELETE CASCADE 
ON UPDATE CASCADE;

-- Foreign Keys for transactions
ALTER TABLE `transactions`
ADD CONSTRAINT `fk_transactions_user_id` 
FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) 
ON DELETE CASCADE 
ON UPDATE CASCADE;

ALTER TABLE `transactions`
ADD CONSTRAINT `fk_transactions_category_id` 
FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) 
ON DELETE RESTRICT 
ON UPDATE CASCADE;

-- Foreign Keys for goals
ALTER TABLE `goals`
ADD CONSTRAINT `fk_goals_user_id` 
FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) 
ON DELETE CASCADE 
ON UPDATE CASCADE;

-- Foreign Keys for goal_transactions
ALTER TABLE `goal_transactions`
ADD CONSTRAINT `fk_goal_transactions_goal_id` 
FOREIGN KEY (`goal_id`) REFERENCES `goals` (`id`) 
ON DELETE CASCADE 
ON UPDATE CASCADE;

ALTER TABLE `goal_transactions`
ADD CONSTRAINT `fk_goal_transactions_transaction_id` 
FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) 
ON DELETE CASCADE 
ON UPDATE CASCADE;

-- Foreign Keys for budgets
ALTER TABLE `budgets`
ADD CONSTRAINT `fk_budgets_user_id` 
FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) 
ON DELETE CASCADE 
ON UPDATE CASCADE;

ALTER TABLE `budgets`
ADD CONSTRAINT `fk_budgets_category_id` 
FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) 
ON DELETE CASCADE 
ON UPDATE CASCADE;

-- Foreign Keys for recurring_transactions
ALTER TABLE `recurring_transactions`
ADD CONSTRAINT `fk_recurring_transactions_user_id` 
FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) 
ON DELETE CASCADE 
ON UPDATE CASCADE;

ALTER TABLE `recurring_transactions`
ADD CONSTRAINT `fk_recurring_transactions_category_id` 
FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) 
ON DELETE CASCADE 
ON UPDATE CASCADE;

-- Foreign Keys for jar_allocations (legacy)
ALTER TABLE `jar_allocations`
ADD CONSTRAINT `fk_jar_allocations_user_id` 
FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) 
ON DELETE CASCADE 
ON UPDATE CASCADE;

-- Foreign Keys for jar_templates
ALTER TABLE `jar_templates`
ADD CONSTRAINT `fk_jar_templates_user_id` 
FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) 
ON DELETE CASCADE 
ON UPDATE CASCADE;

-- Foreign Keys for jar_categories
ALTER TABLE `jar_categories`
ADD CONSTRAINT `fk_jar_categories_jar_id` 
FOREIGN KEY (`jar_id`) REFERENCES `jar_templates` (`id`) 
ON DELETE CASCADE 
ON UPDATE CASCADE;

-- Foreign Keys for jar_allocations_v2
ALTER TABLE `jar_allocations_v2`
ADD CONSTRAINT `fk_jar_allocations_v2_user_id` 
FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) 
ON DELETE CASCADE 
ON UPDATE CASCADE;

ALTER TABLE `jar_allocations_v2`
ADD CONSTRAINT `fk_jar_allocations_v2_jar_template_id` 
FOREIGN KEY (`jar_template_id`) REFERENCES `jar_templates` (`id`) 
ON DELETE CASCADE 
ON UPDATE CASCADE;

-- Additional Indexes for Performance
CREATE INDEX `idx_transactions_user_date` ON `transactions` (`user_id`, `date`);
CREATE INDEX `idx_categories_user_id` ON `categories` (`user_id`);
CREATE INDEX `idx_goals_user_id` ON `goals` (`user_id`);
CREATE INDEX `idx_jar_templates_user_id` ON `jar_templates` (`user_id`);

-- ============================================
-- BƯỚC 4: VIEWS & STORED PROCEDURES
-- (Phụ thuộc vào tables, phải chạy sau)
-- ============================================

-- View: Monthly summary
CREATE VIEW `v_monthly_summary` AS
SELECT 
    t.user_id,
    DATE_FORMAT(t.date, '%Y-%m') AS month,
    t.type,
    COUNT(*) AS transaction_count,
    SUM(t.amount) AS total_amount
FROM transactions t
GROUP BY t.user_id, month, t.type;

-- View: Category summary
CREATE VIEW `v_category_summary` AS
SELECT 
    t.user_id,
    c.id AS category_id,
    c.name AS category_name,
    c.type,
    COUNT(t.id) AS transaction_count,
    SUM(t.amount) AS total_amount
FROM transactions t
INNER JOIN categories c ON t.category_id = c.id
GROUP BY t.user_id, c.id, c.name, c.type;

-- Stored Procedures
DELIMITER $$

CREATE PROCEDURE `sp_get_user_balance`(IN p_user_id INT)
BEGIN
    SELECT 
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS total_income,
        COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS total_expense,
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END), 0) AS balance
    FROM transactions
    WHERE user_id = p_user_id;
END$$

CREATE PROCEDURE `sp_get_budget_status`(IN p_user_id INT, IN p_budget_id INT)
BEGIN
    SELECT 
        b.id,
        b.amount AS budget_amount,
        COALESCE(SUM(t.amount), 0) AS spent_amount,
        b.amount - COALESCE(SUM(t.amount), 0) AS remaining,
        ROUND((COALESCE(SUM(t.amount), 0) / b.amount * 100), 2) AS percentage_used
    FROM budgets b
    LEFT JOIN transactions t ON 
        b.category_id = t.category_id AND 
        b.user_id = t.user_id AND
        t.date BETWEEN b.start_date AND b.end_date
    WHERE b.id = p_budget_id AND b.user_id = p_user_id
    GROUP BY b.id, b.amount;
END$$

DELIMITER ;

-- Triggers
DELIMITER $$

CREATE TRIGGER `trg_transactions_set_type`
BEFORE INSERT ON `transactions`
FOR EACH ROW
BEGIN
    DECLARE cat_type VARCHAR(10);
    SELECT type INTO cat_type FROM categories WHERE id = NEW.category_id;
    SET NEW.type = cat_type;
END$$

DELIMITER ;

-- ============================================
-- BƯỚC 5: DỮ LIỆU MẶC ĐỊNH (DEFAULT CATEGORIES)
-- ============================================

-- Default Income Categories
INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`) VALUES
('Lương', 'income', '#27ae60', 'fa-money-bill-wave', 1),
('Thưởng', 'income', '#f1c40f', 'fa-gift', 1),
('Đầu tư', 'income', '#2980b9', 'fa-chart-line', 1),
('Freelance', 'income', '#d35400', 'fa-laptop-code', 1),
('Thu nhập khác', 'income', '#7f8c8d', 'fa-coins', 1);

-- Default Expense Categories - 6 Jars Method
INSERT INTO `categories` (`name`, `type`, `color`, `icon`, `is_default`) VALUES
-- Necessities (55%)
('Ăn uống', 'expense', '#e74c3c', 'fa-utensils', 1),
('Giao thông', 'expense', '#3498db', 'fa-car', 1),
('Mua sắm', 'expense', '#8e44ad', 'fa-shopping-cart', 1),
('Giải trí', 'expense', '#f39c12', 'fa-film', 1),
('Nhà ở', 'expense', '#1abc9c', 'fa-home', 1),
('Hóa đơn', 'expense', '#34495e', 'fa-file-invoice-dollar', 1),
('Sức khỏe', 'expense', '#e67e22', 'fa-heartbeat', 1),
('Du lịch', 'expense', '#16a085', 'fa-plane', 1),
('Từ thiện', 'expense', '#e74c3c', 'fa-hand-holding-heart', 1);

-- ============================================
-- BƯỚC 6: DỮ LIỆU TEST (DEMO USER & TRANSACTIONS)
-- ============================================

-- Test User (Password: 123456)
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `role`, `is_super_admin`) VALUES
('demo@smartspending.com', 'demo@smartspending.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo User', 'user', 0);

SET @demo_user_id = LAST_INSERT_ID();

-- Custom Categories for Demo User
INSERT INTO `categories` (`user_id`, `name`, `type`, `color`, `icon`, `is_default`) VALUES
(@demo_user_id, 'Cafe & Trà sữa', 'expense', '#d35400', 'fa-coffee', 0),
(@demo_user_id, 'Gym & Thể thao', 'expense', '#1abc9c', 'fa-dumbbell', 0),
(@demo_user_id, 'Freelance Web', 'income', '#27ae60', 'fa-laptop-code', 0);

-- Transactions - October 2025
INSERT INTO `transactions` (`user_id`, `category_id`, `amount`, `type`, `description`, `date`) VALUES
-- Week 1
(@demo_user_id, 1, 15000000.00, 'income', 'Lương tháng 10/2025', '2025-10-01'),
(@demo_user_id, 18, 5000000.00, 'income', 'Dự án website công ty X', '2025-10-02'),
(@demo_user_id, 6, 120000.00, 'expense', 'Ăn sáng phở', '2025-10-01'),
(@demo_user_id, 7, 35000.00, 'expense', 'Xe bus đi làm', '2025-10-01'),
(@demo_user_id, 16, 50000.00, 'expense', 'Cafe sáng văn phòng', '2025-10-02'),
(@demo_user_id, 6, 180000.00, 'expense', 'Ăn trưa buffet', '2025-10-02'),
(@demo_user_id, 8, 800000.00, 'expense', 'Mua giày thể thao', '2025-10-03'),
(@demo_user_id, 6, 150000.00, 'expense', 'Ăn tối lẩu', '2025-10-03'),
(@demo_user_id, 7, 70000.00, 'expense', 'Grab về nhà', '2025-10-03'),
(@demo_user_id, 9, 250000.00, 'expense', 'Vé xem phim', '2025-10-04'),
(@demo_user_id, 16, 45000.00, 'expense', 'Trà sữa', '2025-10-05'),
(@demo_user_id, 6, 130000.00, 'expense', 'Ăn trưa cơm văn phòng', '2025-10-05'),
(@demo_user_id, 17, 500000.00, 'expense', 'Phí tập gym tháng 10', '2025-10-06'),
(@demo_user_id, 11, 380000.00, 'expense', 'Hóa đơn điện tháng 10', '2025-10-07'),
-- Week 2
(@demo_user_id, 6, 110000.00, 'expense', 'Ăn sáng bánh mì', '2025-10-08'),
(@demo_user_id, 7, 40000.00, 'expense', 'Xe ôm đi làm', '2025-10-08'),
(@demo_user_id, 16, 60000.00, 'expense', 'Cafe trưa', '2025-10-09'),
(@demo_user_id, 6, 200000.00, 'expense', 'Ăn tối nhà hàng', '2025-10-09'),
(@demo_user_id, 8, 1200000.00, 'expense', 'Mua áo khoác', '2025-10-10'),
(@demo_user_id, 12, 500000.00, 'expense', 'Khám răng định kỳ', '2025-10-10'),
(@demo_user_id, 6, 140000.00, 'expense', 'Ăn trưa quán ăn', '2025-10-11'),
(@demo_user_id, 9, 150000.00, 'expense', 'Karaoke với bạn', '2025-10-12'),
(@demo_user_id, 16, 55000.00, 'expense', 'Trà chanh', '2025-10-13'),
(@demo_user_id, 6, 160000.00, 'expense', 'Ăn tối BBQ', '2025-10-13'),
(@demo_user_id, 7, 120000.00, 'expense', 'Grab đi chơi', '2025-10-14'),
-- Week 3
(@demo_user_id, 2, 2000000.00, 'income', 'Thưởng dự án hoàn thành', '2025-10-15'),
(@demo_user_id, 6, 125000.00, 'expense', 'Ăn sáng', '2025-10-15'),
(@demo_user_id, 11, 250000.00, 'expense', 'Hóa đơn nước', '2025-10-15'),
(@demo_user_id, 16, 50000.00, 'expense', 'Cafe', '2025-10-16'),
(@demo_user_id, 6, 190000.00, 'expense', 'Ăn trưa nhà hàng', '2025-10-16'),
(@demo_user_id, 8, 650000.00, 'expense', 'Mua quần jean', '2025-10-17'),
(@demo_user_id, 6, 145000.00, 'expense', 'Ăn tối', '2025-10-17'),
(@demo_user_id, 9, 300000.00, 'expense', 'Vé concert', '2025-10-18'),
(@demo_user_id, 16, 65000.00, 'expense', 'Trà sữa premium', '2025-10-19'),
(@demo_user_id, 6, 170000.00, 'expense', 'Ăn trưa sushi', '2025-10-19'),
(@demo_user_id, 7, 200000.00, 'expense', 'Đổ xăng xe máy', '2025-10-20'),
(@demo_user_id, 10, 2500000.00, 'expense', 'Tiền thuê nhà tháng 10', '2025-10-20'),
(@demo_user_id, 6, 180000.00, 'expense', 'Ăn tối', '2025-10-21'),
-- Week 4
(@demo_user_id, 6, 135000.00, 'expense', 'Ăn sáng', '2025-10-22'),
(@demo_user_id, 16, 55000.00, 'expense', 'Cafe', '2025-10-22'),
(@demo_user_id, 6, 160000.00, 'expense', 'Ăn trưa', '2025-10-23'),
(@demo_user_id, 12, 350000.00, 'expense', 'Mua thuốc', '2025-10-23'),
(@demo_user_id, 6, 200000.00, 'expense', 'Ăn tối buffet', '2025-10-24'),
(@demo_user_id, 9, 180000.00, 'expense', 'Bowling với bạn', '2025-10-25'),
(@demo_user_id, 16, 60000.00, 'expense', 'Trà sữa', '2025-10-26'),
(@demo_user_id, 6, 150000.00, 'expense', 'Ăn trưa', '2025-10-26'),
(@demo_user_id, 8, 450000.00, 'expense', 'Mua giày', '2025-10-27'),
(@demo_user_id, 6, 175000.00, 'expense', 'Ăn tối hotpot', '2025-10-27'),
(@demo_user_id, 11, 150000.00, 'expense', 'Hóa đơn internet', '2025-10-28'),
(@demo_user_id, 6, 140000.00, 'expense', 'Ăn trưa', '2025-10-29'),
(@demo_user_id, 16, 50000.00, 'expense', 'Cafe', '2025-10-30'),
(@demo_user_id, 6, 190000.00, 'expense', 'Ăn tối cuối tháng', '2025-10-31');

-- Transactions - November 2025
INSERT INTO `transactions` (`user_id`, `category_id`, `amount`, `type`, `description`, `date`) VALUES
-- Week 1
(@demo_user_id, 1, 15000000.00, 'income', 'Lương tháng 11/2025', '2025-11-01'),
(@demo_user_id, 18, 3500000.00, 'income', 'Freelance thiết kế landing page', '2025-11-03'),
(@demo_user_id, 6, 125000.00, 'expense', 'Ăn sáng phở', '2025-11-01'),
(@demo_user_id, 7, 40000.00, 'expense', 'Xe bus', '2025-11-01'),
(@demo_user_id, 16, 55000.00, 'expense', 'Cafe sáng', '2025-11-02'),
(@demo_user_id, 6, 170000.00, 'expense', 'Ăn trưa', '2025-11-02'),
(@demo_user_id, 8, 1100000.00, 'expense', 'Mua áo vest', '2025-11-03'),
(@demo_user_id, 6, 160000.00, 'expense', 'Ăn tối', '2025-11-03'),
(@demo_user_id, 9, 280000.00, 'expense', 'Xem phim IMAX', '2025-11-04'),
(@demo_user_id, 16, 60000.00, 'expense', 'Trà sữa', '2025-11-05'),
(@demo_user_id, 6, 145000.00, 'expense', 'Ăn trưa', '2025-11-05'),
(@demo_user_id, 11, 400000.00, 'expense', 'Hóa đơn điện tháng 11', '2025-11-06'),
(@demo_user_id, 6, 180000.00, 'expense', 'Ăn tối BBQ', '2025-11-07'),
-- Week 2
(@demo_user_id, 6, 130000.00, 'expense', 'Ăn sáng', '2025-11-08'),
(@demo_user_id, 7, 45000.00, 'expense', 'Grab đi làm', '2025-11-08'),
(@demo_user_id, 16, 50000.00, 'expense', 'Cafe trưa', '2025-11-09'),
(@demo_user_id, 6, 165000.00, 'expense', 'Ăn trưa nhà hàng', '2025-11-09'),
(@demo_user_id, 12, 800000.00, 'expense', 'Khám sức khỏe tổng quát', '2025-11-10'),
(@demo_user_id, 6, 155000.00, 'expense', 'Ăn tối', '2025-11-10'),
(@demo_user_id, 17, 500000.00, 'expense', 'Phí gym tháng 11', '2025-11-11'),
(@demo_user_id, 9, 200000.00, 'expense', 'Trò chơi điện tử', '2025-11-11'),
(@demo_user_id, 16, 65000.00, 'expense', 'Trà chanh', '2025-11-12'),
(@demo_user_id, 6, 175000.00, 'expense', 'Ăn trưa', '2025-11-12'),
(@demo_user_id, 8, 750000.00, 'expense', 'Mua túi xách', '2025-11-13'),
(@demo_user_id, 6, 190000.00, 'expense', 'Ăn tối hotpot', '2025-11-13'),
(@demo_user_id, 7, 150000.00, 'expense', 'Đổ xăng', '2025-11-14'),
-- Week 3
(@demo_user_id, 2, 1500000.00, 'income', 'Thưởng hiệu suất', '2025-11-15'),
(@demo_user_id, 6, 140000.00, 'expense', 'Ăn sáng', '2025-11-15'),
(@demo_user_id, 11, 260000.00, 'expense', 'Hóa đơn nước', '2025-11-15'),
(@demo_user_id, 16, 55000.00, 'expense', 'Cafe', '2025-11-16'),
(@demo_user_id, 6, 180000.00, 'expense', 'Ăn trưa', '2025-11-16'),
(@demo_user_id, 9, 350000.00, 'expense', 'Karaoke', '2025-11-17'),
(@demo_user_id, 6, 165000.00, 'expense', 'Ăn tối', '2025-11-17'),
(@demo_user_id, 8, 900000.00, 'expense', 'Mua đồng hồ', '2025-11-18'),
(@demo_user_id, 16, 70000.00, 'expense', 'Trà sữa cheese', '2025-11-19'),
(@demo_user_id, 6, 195000.00, 'expense', 'Ăn trưa sushi', '2025-11-19'),
(@demo_user_id, 10, 2500000.00, 'expense', 'Tiền thuê nhà tháng 11', '2025-11-20'),
(@demo_user_id, 6, 170000.00, 'expense', 'Ăn tối', '2025-11-21'),
-- Week 4
(@demo_user_id, 6, 145000.00, 'expense', 'Ăn sáng', '2025-11-22'),
(@demo_user_id, 16, 60000.00, 'expense', 'Cafe', '2025-11-22'),
(@demo_user_id, 6, 175000.00, 'expense', 'Ăn trưa', '2025-11-23'),
(@demo_user_id, 12, 420000.00, 'expense', 'Khám mắt và làm kính', '2025-11-23'),
(@demo_user_id, 6, 210000.00, 'expense', 'Ăn tối buffet', '2025-11-24'),
(@demo_user_id, 9, 250000.00, 'expense', 'Vé concert nhạc', '2025-11-25'),
(@demo_user_id, 16, 65000.00, 'expense', 'Trà sữa', '2025-11-26'),
(@demo_user_id, 6, 160000.00, 'expense', 'Ăn trưa', '2025-11-26'),
(@demo_user_id, 8, 550000.00, 'expense', 'Mua ví da', '2025-11-27'),
(@demo_user_id, 6, 185000.00, 'expense', 'Ăn tối', '2025-11-27'),
(@demo_user_id, 11, 160000.00, 'expense', 'Hóa đơn internet', '2025-11-28'),
(@demo_user_id, 6, 155000.00, 'expense', 'Ăn trưa', '2025-11-29'),
(@demo_user_id, 16, 55000.00, 'expense', 'Cafe', '2025-11-30'),
(@demo_user_id, 6, 200000.00, 'expense', 'Ăn tối cuối tháng', '2025-11-30');

-- Goals
INSERT INTO `goals` (`user_id`, `name`, `description`, `target_amount`, `deadline`, `status`) VALUES
(@demo_user_id, 'Mua iPhone 16 Pro Max', 'Tiết kiệm mua điện thoại mới', 35000000.00, '2025-12-31', 'active'),
(@demo_user_id, 'Du lịch Phú Quốc', 'Kỳ nghỉ Tết Nguyên Đán 2026', 20000000.00, '2026-01-31', 'active'),
(@demo_user_id, 'Quỹ khẩn cấp', 'Dự phòng cho các tình huống bất ngờ', 50000000.00, '2026-06-30', 'active');

-- Recurring Transactions
INSERT INTO `recurring_transactions` (`user_id`, `category_id`, `amount`, `type`, `description`, `frequency`, `start_date`, `next_occurrence`, `is_active`) VALUES
(@demo_user_id, 1, 15000000.00, 'income', 'Lương hàng tháng', 'monthly', '2025-10-01', '2025-12-01', 1),
(@demo_user_id, 10, 2500000.00, 'expense', 'Tiền thuê nhà', 'monthly', '2025-10-20', '2025-12-20', 1),
(@demo_user_id, 17, 500000.00, 'expense', 'Phí gym', 'monthly', '2025-10-11', '2025-12-11', 1),
(@demo_user_id, 11, 400000.00, 'expense', 'Hóa đơn điện', 'monthly', '2025-10-07', '2025-12-07', 1),
(@demo_user_id, 11, 260000.00, 'expense', 'Hóa đơn nước', 'monthly', '2025-10-15', '2025-12-15', 1),
(@demo_user_id, 11, 160000.00, 'expense', 'Hóa đơn internet', 'monthly', '2025-10-28', '2025-12-28', 1);

-- ============================================
-- VERIFICATION QUERIES
-- ============================================

-- Verify Foreign Keys
SELECT 
    TABLE_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    DELETE_RULE,
    UPDATE_RULE
FROM information_schema.REFERENTIAL_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = 'quan_ly_chi_tieu'
ORDER BY TABLE_NAME;

-- Verify Data
SELECT 'Total Users' as info, COUNT(*) as count FROM users
UNION ALL
SELECT 'Total Categories', COUNT(*) FROM categories
UNION ALL
SELECT 'Total Transactions', COUNT(*) FROM transactions
UNION ALL
SELECT 'Total Goals', COUNT(*) FROM goals;

-- ============================================
-- END OF SETUP
-- ============================================
-- SUMMARY:
-- ✅ Database created with proper schema
-- ✅ All tables with Foreign Keys and Indexes
-- ✅ Views and Stored Procedures
-- ✅ Default categories (14 categories)
-- ✅ Demo user with 106 transactions (Oct-Nov 2025)
-- ✅ Goals and Recurring transactions
-- 
-- LOGIN: demo@smartspending.com / 123456
-- ============================================
