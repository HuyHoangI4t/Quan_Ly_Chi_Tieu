-- ============================================
-- SmartSpending - Test Data (October & November 2025)
-- Version: 1.0.0
-- Date: December 1, 2025
-- Description: Dữ liệu test cho 2 tháng 10 và 11/2025
-- Note: Chạy file full_schema.sql trước khi chạy file này
-- ============================================

USE `quan_ly_chi_tieu`;

-- ============================================
-- TEST USERS
-- ============================================
-- Password: password123
INSERT INTO `users` (`username`, `email`, `password`, `full_name`) VALUES
('testuser', 'testuser@smartspending.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nguyễn Văn Test');

-- ============================================
-- CUSTOM CATEGORIES (User specific)
-- ============================================
INSERT INTO `categories` (`user_id`, `name`, `type`, `color`, `icon`, `is_default`) VALUES
(1, 'Cafe & Trà sữa', 'expense', '#d35400', 'fa-coffee', 0),
(1, 'Gym & Thể thao', 'expense', '#1abc9c', 'fa-dumbbell', 0),
(1, 'Freelance Web', 'income', '#27ae60', 'fa-laptop-code', 0);

-- ============================================
-- TRANSACTIONS - OCTOBER 2025 (User ID: 1)
-- ============================================

-- Week 1: Oct 01-07
INSERT INTO `transactions` (`user_id`, `category_id`, `amount`, `type`, `description`, `date`) VALUES
(1, 11, 15000000.00, 'income', 'Lương tháng 10/2025', '2025-10-01'),
(1, 18, 5000000.00, 'income', 'Dự án website công ty X', '2025-10-02'),
(1, 1, 120000.00, 'expense', 'Ăn sáng phở', '2025-10-01'),
(1, 2, 35000.00, 'expense', 'Xe bus đi làm', '2025-10-01'),
(1, 16, 50000.00, 'expense', 'Cafe sáng văn phòng', '2025-10-02'),
(1, 1, 180000.00, 'expense', 'Ăn trưa buffet', '2025-10-02'),
(1, 3, 800000.00, 'expense', 'Mua giày thể thao', '2025-10-03'),
(1, 1, 150000.00, 'expense', 'Ăn tối lẩu', '2025-10-03'),
(1, 2, 70000.00, 'expense', 'Grab về nhà', '2025-10-03'),
(1, 4, 250000.00, 'expense', 'Vé xem phim', '2025-10-04'),
(1, 16, 45000.00, 'expense', 'Trà sữa', '2025-10-05'),
(1, 1, 130000.00, 'expense', 'Ăn trưa cơm văn phòng', '2025-10-05'),
(1, 17, 500000.00, 'expense', 'Phí tập gym tháng 10', '2025-10-06'),
(1, 6, 380000.00, 'expense', 'Hóa đơn điện tháng 10', '2025-10-07');

-- Week 2: Oct 08-14
INSERT INTO `transactions` (`user_id`, `category_id`, `amount`, `type`, `description`, `date`) VALUES
(1, 1, 110000.00, 'expense', 'Ăn sáng bánh mì', '2025-10-08'),
(1, 2, 40000.00, 'expense', 'Xe ôm đi làm', '2025-10-08'),
(1, 16, 60000.00, 'expense', 'Cafe trưa', '2025-10-09'),
(1, 1, 200000.00, 'expense', 'Ăn tối nhà hàng', '2025-10-09'),
(1, 3, 1200000.00, 'expense', 'Mua áo khoác', '2025-10-10'),
(1, 7, 500000.00, 'expense', 'Khám răng định kỳ', '2025-10-10'),
(1, 1, 140000.00, 'expense', 'Ăn trưa quán ăn', '2025-10-11'),
(1, 4, 150000.00, 'expense', 'Karaoke với bạn', '2025-10-12'),
(1, 16, 55000.00, 'expense', 'Trà chanh', '2025-10-13'),
(1, 1, 160000.00, 'expense', 'Ăn tối BBQ', '2025-10-13'),
(1, 2, 120000.00, 'expense', 'Grab đi chơi', '2025-10-14');

-- Week 3: Oct 15-21
INSERT INTO `transactions` (`user_id`, `category_id`, `amount`, `type`, `description`, `date`) VALUES
(1, 12, 2000000.00, 'income', 'Thưởng dự án hoàn thành', '2025-10-15'),
(1, 1, 125000.00, 'expense', 'Ăn sáng', '2025-10-15'),
(1, 6, 250000.00, 'expense', 'Hóa đơn nước', '2025-10-15'),
(1, 16, 50000.00, 'expense', 'Cafe', '2025-10-16'),
(1, 1, 190000.00, 'expense', 'Ăn trưa nhà hàng', '2025-10-16'),
(1, 3, 650000.00, 'expense', 'Mua quần jean', '2025-10-17'),
(1, 1, 145000.00, 'expense', 'Ăn tối', '2025-10-17'),
(1, 4, 300000.00, 'expense', 'Vé concert', '2025-10-18'),
(1, 16, 65000.00, 'expense', 'Trà sữa premium', '2025-10-19'),
(1, 1, 170000.00, 'expense', 'Ăn trưa sushi', '2025-10-19'),
(1, 2, 200000.00, 'expense', 'Đổ xăng xe máy', '2025-10-20'),
(1, 5, 2500000.00, 'expense', 'Tiền thuê nhà tháng 10', '2025-10-20'),
(1, 1, 180000.00, 'expense', 'Ăn tối', '2025-10-21');

-- Week 4: Oct 22-31
INSERT INTO `transactions` (`user_id`, `category_id`, `amount`, `type`, `description`, `date`) VALUES
(1, 1, 135000.00, 'expense', 'Ăn sáng', '2025-10-22'),
(1, 16, 55000.00, 'expense', 'Cafe', '2025-10-22'),
(1, 1, 160000.00, 'expense', 'Ăn trưa', '2025-10-23'),
(1, 7, 350000.00, 'expense', 'Mua thuốc', '2025-10-23'),
(1, 1, 200000.00, 'expense', 'Ăn tối buffet', '2025-10-24'),
(1, 4, 180000.00, 'expense', 'Bowling với bạn', '2025-10-25'),
(1, 16, 60000.00, 'expense', 'Trà sữa', '2025-10-26'),
(1, 1, 150000.00, 'expense', 'Ăn trưa', '2025-10-26'),
(1, 3, 450000.00, 'expense', 'Mua giày', '2025-10-27'),
(1, 1, 175000.00, 'expense', 'Ăn tối hotpot', '2025-10-27'),
(1, 6, 150000.00, 'expense', 'Hóa đơn internet', '2025-10-28'),
(1, 1, 140000.00, 'expense', 'Ăn trưa', '2025-10-29'),
(1, 16, 50000.00, 'expense', 'Cafe', '2025-10-30'),
(1, 1, 190000.00, 'expense', 'Ăn tối cuối tháng', '2025-10-31');

-- ============================================
-- TRANSACTIONS - NOVEMBER 2025 (User ID: 1)
-- ============================================

-- Week 1: Nov 01-07
INSERT INTO `transactions` (`user_id`, `category_id`, `amount`, `type`, `description`, `date`) VALUES
(1, 11, 15000000.00, 'income', 'Lương tháng 11/2025', '2025-11-01'),
(1, 18, 3500000.00, 'income', 'Freelance thiết kế landing page', '2025-11-03'),
(1, 1, 125000.00, 'expense', 'Ăn sáng phở', '2025-11-01'),
(1, 2, 40000.00, 'expense', 'Xe bus', '2025-11-01'),
(1, 16, 55000.00, 'expense', 'Cafe sáng', '2025-11-02'),
(1, 1, 170000.00, 'expense', 'Ăn trưa', '2025-11-02'),
(1, 3, 1100000.00, 'expense', 'Mua áo vest', '2025-11-03'),
(1, 1, 160000.00, 'expense', 'Ăn tối', '2025-11-03'),
(1, 4, 280000.00, 'expense', 'Xem phim IMAX', '2025-11-04'),
(1, 16, 60000.00, 'expense', 'Trà sữa', '2025-11-05'),
(1, 1, 145000.00, 'expense', 'Ăn trưa', '2025-11-05'),
(1, 6, 400000.00, 'expense', 'Hóa đơn điện tháng 11', '2025-11-06'),
(1, 1, 180000.00, 'expense', 'Ăn tối BBQ', '2025-11-07');

-- Week 2: Nov 08-14
INSERT INTO `transactions` (`user_id`, `category_id`, `amount`, `type`, `description`, `date`) VALUES
(1, 1, 130000.00, 'expense', 'Ăn sáng', '2025-11-08'),
(1, 2, 45000.00, 'expense', 'Grab đi làm', '2025-11-08'),
(1, 16, 50000.00, 'expense', 'Cafe trưa', '2025-11-09'),
(1, 1, 165000.00, 'expense', 'Ăn trưa nhà hàng', '2025-11-09'),
(1, 7, 800000.00, 'expense', 'Khám sức khỏe tổng quát', '2025-11-10'),
(1, 1, 155000.00, 'expense', 'Ăn tối', '2025-11-10'),
(1, 17, 500000.00, 'expense', 'Phí gym tháng 11', '2025-11-11'),
(1, 4, 200000.00, 'expense', 'Trò chơi điện tử', '2025-11-11'),
(1, 16, 65000.00, 'expense', 'Trà chanh', '2025-11-12'),
(1, 1, 175000.00, 'expense', 'Ăn trưa', '2025-11-12'),
(1, 3, 750000.00, 'expense', 'Mua túi xách', '2025-11-13'),
(1, 1, 190000.00, 'expense', 'Ăn tối hotpot', '2025-11-13'),
(1, 2, 150000.00, 'expense', 'Đổ xăng', '2025-11-14');

-- Week 3: Nov 15-21
INSERT INTO `transactions` (`user_id`, `category_id`, `amount`, `type`, `description`, `date`) VALUES
(1, 12, 1500000.00, 'income', 'Thưởng hiệu suất', '2025-11-15'),
(1, 1, 140000.00, 'expense', 'Ăn sáng', '2025-11-15'),
(1, 6, 260000.00, 'expense', 'Hóa đơn nước', '2025-11-15'),
(1, 16, 55000.00, 'expense', 'Cafe', '2025-11-16'),
(1, 1, 180000.00, 'expense', 'Ăn trưa', '2025-11-16'),
(1, 4, 350000.00, 'expense', 'Karaoke', '2025-11-17'),
(1, 1, 165000.00, 'expense', 'Ăn tối', '2025-11-17'),
(1, 3, 900000.00, 'expense', 'Mua đồng hồ', '2025-11-18'),
(1, 16, 70000.00, 'expense', 'Trà sữa cheese', '2025-11-19'),
(1, 1, 195000.00, 'expense', 'Ăn trưa sushi', '2025-11-19'),
(1, 5, 2500000.00, 'expense', 'Tiền thuê nhà tháng 11', '2025-11-20'),
(1, 1, 170000.00, 'expense', 'Ăn tối', '2025-11-21');

-- Week 4: Nov 22-30
INSERT INTO `transactions` (`user_id`, `category_id`, `amount`, `type`, `description`, `date`) VALUES
(1, 1, 145000.00, 'expense', 'Ăn sáng', '2025-11-22'),
(1, 16, 60000.00, 'expense', 'Cafe', '2025-11-22'),
(1, 1, 175000.00, 'expense', 'Ăn trưa', '2025-11-23'),
(1, 7, 420000.00, 'expense', 'Khám mắt và làm kính', '2025-11-23'),
(1, 1, 210000.00, 'expense', 'Ăn tối buffet', '2025-11-24'),
(1, 4, 250000.00, 'expense', 'Vé concert nhạc', '2025-11-25'),
(1, 16, 65000.00, 'expense', 'Trà sữa', '2025-11-26'),
(1, 1, 160000.00, 'expense', 'Ăn trưa', '2025-11-26'),
(1, 3, 550000.00, 'expense', 'Mua ví da', '2025-11-27'),
(1, 1, 185000.00, 'expense', 'Ăn tối', '2025-11-27'),
(1, 6, 160000.00, 'expense', 'Hóa đơn internet', '2025-11-28'),
(1, 1, 155000.00, 'expense', 'Ăn trưa', '2025-11-29'),
(1, 16, 55000.00, 'expense', 'Cafe', '2025-11-30'),
(1, 1, 200000.00, 'expense', 'Ăn tối cuối tháng', '2025-11-30');

-- ============================================
-- BUDGETS - October & November 2025
-- ============================================
INSERT INTO `budgets` (`user_id`, `category_id`, `amount`, `period`, `start_date`, `end_date`, `alert_threshold`, `is_active`) VALUES
(1, 1, 5000000.00, 'monthly', '2025-10-01', '2025-10-31', 80, 1),
(1, 2, 1000000.00, 'monthly', '2025-10-01', '2025-10-31', 80, 1),
(1, 3, 2000000.00, 'monthly', '2025-10-01', '2025-10-31', 80, 1),
(1, 4, 1500000.00, 'monthly', '2025-10-01', '2025-10-31', 80, 1),
(1, 1, 5000000.00, 'monthly', '2025-11-01', '2025-11-30', 80, 1),
(1, 2, 1000000.00, 'monthly', '2025-11-01', '2025-11-30', 80, 1),
(1, 3, 2000000.00, 'monthly', '2025-11-01', '2025-11-30', 80, 1),
(1, 4, 1500000.00, 'monthly', '2025-11-01', '2025-11-30', 80, 1);

-- ============================================
-- GOALS
-- ============================================
INSERT INTO `goals` (`user_id`, `name`, `description`, `target_amount`, `deadline`, `status`) VALUES
(1, 'Mua iPhone 16 Pro Max', 'Tiết kiệm mua điện thoại mới', 35000000.00, '2025-12-31', 'active'),
(1, 'Du lịch Phú Quốc', 'Kỳ nghỉ Tết Nguyên Đán 2026', 20000000.00, '2026-01-31', 'active'),
(1, 'Quỹ khẩn cấp', 'Dự phòng cho các tình huống bất ngờ', 50000000.00, '2026-06-30', 'active');

-- ============================================
-- RECURRING TRANSACTIONS
-- ============================================
INSERT INTO `recurring_transactions` (`user_id`, `category_id`, `amount`, `type`, `description`, `frequency`, `start_date`, `next_occurrence`, `is_active`) VALUES
(1, 11, 15000000.00, 'income', 'Lương hàng tháng', 'monthly', '2025-10-01', '2025-12-01', 1),
(1, 5, 2500000.00, 'expense', 'Tiền thuê nhà', 'monthly', '2025-10-20', '2025-12-20', 1),
(1, 17, 500000.00, 'expense', 'Phí gym', 'monthly', '2025-10-11', '2025-12-11', 1),
(1, 6, 400000.00, 'expense', 'Hóa đơn điện', 'monthly', '2025-10-07', '2025-12-07', 1),
(1, 6, 260000.00, 'expense', 'Hóa đơn nước', 'monthly', '2025-10-15', '2025-12-15', 1),
(1, 6, 160000.00, 'expense', 'Hóa đơn internet', 'monthly', '2025-10-28', '2025-12-28', 1);

-- ============================================
-- SUMMARY
-- ============================================
-- October 2025:
--   Income: 22,000,000 VND (Salary: 15M + Freelance: 5M + Bonus: 2M)
--   Expense: ~11,000,000 VND
--   Balance: ~11,000,000 VND
--
-- November 2025:
--   Income: 20,000,000 VND (Salary: 15M + Freelance: 3.5M + Bonus: 1.5M)
--   Expense: ~10,500,000 VND
--   Balance: ~9,500,000 VND
-- ============================================
