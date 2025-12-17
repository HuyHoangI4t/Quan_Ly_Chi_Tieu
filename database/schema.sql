-- ==========================================================
-- SMART SPENDING - FINAL DATABASE (FULL OP - MATCHING DOCS)
-- Updated: 2025-12-17
-- ==========================================================

-- 1. SETUP DATABASE
DROP DATABASE IF EXISTS `quan_ly_chi_tieu`;
CREATE DATABASE `quan_ly_chi_tieu` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `quan_ly_chi_tieu`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";
SET FOREIGN_KEY_CHECKS = 0;

-- ==========================================================
-- 2. CREATE TABLES (Theo danh sách bảng trong hình)
-- ==========================================================

-- 2.1. Users (STT 1: Người dùng & Cài đặt)
-- "Lưu trữ thông tin... và các cài đặt liên quan"
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `avatar` varchar(255) DEFAULT 'https://www.svgrepo.com/show/452030/avatar-default.svg',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  -- Các cột cài đặt (Settings) được gộp vào đây theo mô tả
  `notify_budget_limit` tinyint(1) DEFAULT 1,
  `notify_goal_reminder` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.2. Categories (STT 2: Danh mục)
-- "Quản lý danh mục thu - chi, phân cấp parent_id"
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL, -- NULL là danh mục hệ thống, có ID là danh mục riêng
  `parent_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `group_type` enum('nec', 'ffa', 'ltss', 'edu', 'play', 'give', 'none') NOT NULL DEFAULT 'none',
  `color` varchar(7) DEFAULT '#000000',
  `icon` varchar(50) DEFAULT 'fa-tag',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_categories_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.3. Transactions (STT 3: Giao dịch)
-- "Lưu lịch sử giao dịch thu - chi"
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `date` date NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('income','expense') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_transactions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_transactions_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.4. Budgets (STT 4: Ngân sách)
-- "Quản lý ngân sách chi tiêu theo chu kỳ"
CREATE TABLE `budgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `period` enum('weekly','monthly','yearly') NOT NULL DEFAULT 'monthly',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `alert_threshold` int(11) DEFAULT 80, -- Cảnh báo khi đạt % này
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_budgets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_budgets_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.5. Goals (STT 5: Mục tiêu)
-- "Lưu thông tin các mục tiêu tiết kiệm"
CREATE TABLE `goals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `target_amount` decimal(15,2) NOT NULL, -- Số tiền mục tiêu
  `current_amount` decimal(15,2) DEFAULT 0.00, -- Số tiền hiện tại
  `deadline` date DEFAULT NULL, -- Thời hạn
  `category_id` int(11) DEFAULT NULL,
  `status` enum('active','completed') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_goals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.6. Recurring_transactions (STT 6: Giao dịch định kỳ)
-- "Quản lý các giao dịch định kỳ, tự động hoá"
CREATE TABLE `recurring_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `frequency` enum('daily','weekly','monthly','yearly') NOT NULL DEFAULT 'monthly',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_recurring_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_recurring_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.7. User_budget_settings (STT 7: Cấu hình hũ)
-- "Lưu cấu hình phân bổ ngân sách theo phương pháp 6 chiếc hũ"
CREATE TABLE `user_budget_settings` (
  `user_id` int(11) PRIMARY KEY,
  `nec_percent` int(3) DEFAULT 55, -- Thiết yếu
  `ffa_percent` int(3) DEFAULT 10, -- Tự do tài chính
  `ltss_percent` int(3) DEFAULT 10, -- Tiết kiệm dài hạn
  `edu_percent` int(3) DEFAULT 10, -- Giáo dục
  `play_percent` int(3) DEFAULT 10, -- Hưởng thụ
  `give_percent` int(3) DEFAULT 5, -- Cho đi
  CONSTRAINT `fk_settings_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- BẢNG PHỤ TRỢ (Không có trong hình nhưng cần thiết để chức năng "chia hũ" hoạt động)
CREATE TABLE `user_wallets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `jar_code` enum('nec', 'ffa', 'ltss', 'edu', 'play', 'give') NOT NULL,
  `balance` decimal(15,2) DEFAULT 0.00,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_jar` (`user_id`, `jar_code`),
  CONSTRAINT `fk_wallets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `target_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_system_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==========================================================
-- 3. INSERT DATA (SEED)
-- ==========================================================

-- 3.1. Insert Admin User
INSERT INTO `users` (id, username, email, password, full_name, role, notify_budget_limit, notify_goal_reminder) VALUES
(1, 'Admin', 'admin@gmail.com', '$2y$10$M12D3rP.nNSdTxDMq/FQbeJfKwrPHoJSq9.itE/N3gZVt.afkEft.', 'Admin Vip Pro', 'admin', 1, 1);

-- 3.2. INSERT CATEGORIES
INSERT INTO `categories` (id, parent_id, name, type, group_type, color, icon, is_default) VALUES 
(1, NULL, 'Khoản Chi', 'expense', 'none', '#E74C3C', 'fa-wallet', 1);

INSERT INTO `categories` (id, parent_id, name, type, group_type, color, icon, is_default) VALUES
(2, 2, 'Hoá đơn', 'expense', 'nec', '#3498DB', 'fa-file-invoice-dollar', 1),
(3, 2, 'Thực phẩm ăn uống', 'expense', 'nec', '#E67E22', 'fa-utensils', 1),
(4, 2, 'Du lịch di chuyển', 'expense', 'play', '#F1C40F', 'fa-plane-departure', 1),
(5, 2, 'Sức khoẻ', 'expense', 'nec', '#2ECC71', 'fa-heartbeat', 1),
(6, 2, 'Chi tiêu cá nhân', 'expense', 'nec', '#9B59B6', 'fa-user-circle', 1),
(7, 2, 'Mua sắm', 'expense', 'play', '#FF9FF3', 'fa-shopping-bag', 1),
(8, 2, 'Giáo dục', 'expense', 'edu', '#34495E', 'fa-graduation-cap', 1),
(9, 2, 'Giải trí', 'expense', 'play', '#D35400', 'fa-gamepad', 1),
(10, 2, 'Đầu tư tiết kiệm', 'expense', 'ffa', '#27AE60', 'fa-piggy-bank', 1),
(11, 2, 'Kinh doanh', 'expense', 'nec', '#7F8C8D', 'fa-briefcase', 1),
(12, 2, 'Trả nợ', 'expense', 'nec', '#C0392B', 'fa-money-bill-wave', 1),
(13, 2, 'Từ thiện', 'expense', 'give', '#FF6B6B', 'fa-hand-holding-heart', 1),
(14, 2, 'Dịch vụ tiện ích', 'expense', 'nec', '#1ABC9C', 'fa-bolt', 1);

INSERT INTO `categories` (id, parent_id, name, type, group_type, color, icon, is_default) VALUES 
(15, NULL, 'Khoản Thu', 'income', 'none', '#2ECC71', 'fa-hand-holding-usd', 1);

INSERT INTO `categories` (id, parent_id, name, type, group_type, color, icon, is_default) VALUES
(16, 15, 'Lương', 'income', 'none', '#27AE60', 'fa-money-bill', 1),
(17, 15, 'Thưởng', 'income', 'none', '#F1C40F', 'fa-gift', 1),
(18, 15, 'Lãi suất', 'income', 'none', '#E67E22', 'fa-percent', 1),
(19, 15, 'Thu nhập khác', 'income', 'none', '#3498DB', 'fa-coins', 1);

INSERT INTO `categories` (id, parent_id, name, type, group_type, color, icon, is_default) VALUES 
(20, NULL, 'Vay & Nợ', 'income', 'none', '#95A5A6', 'fa-balance-scale', 1);
INSERT INTO `categories` (id, parent_id, name, type, group_type, color, icon, is_default) VALUES 
(21, 20, 'Đi vay', 'income', 'none', '#7F8C8D', 'fa-hand-holding-medical', 1),
(22, 20, 'Thu nợ', 'income', 'none', '#27AE60', 'fa-check-circle', 1);

-- 3.3. INSERT TRANSACTIONS (Dữ liệu mẫu cho Admin test)
INSERT INTO `transactions` (user_id, category_id, amount, date, description, type) VALUES
(2, 16, 20000000, '2025-11-01', 'Lương tháng 11', 'income'),
(2, 3, -500000, '2025-11-02', 'Đi chợ', 'expense'),
(2, 3, -100000, '2025-11-03', 'Ăn sáng', 'expense'),
(2, 3, -200000, '2025-11-05', 'Tiền điện', 'expense');

-- 3.4. INIT WALLETS
INSERT IGNORE INTO `user_wallets` (user_id, jar_code, balance) VALUES 
(2, 'nec', 0), (2, 'ffa', 0), (2, 'ltss', 0), (2, 'edu', 0), (2, 'play', 0), (2, 'give', 0);

-- 3.5. INIT SETTINGS
INSERT IGNORE INTO `user_budget_settings` (user_id) VALUES (2);

SET FOREIGN_KEY_CHECKS = 1;